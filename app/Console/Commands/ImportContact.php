<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Models\Contact;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

final class ImportContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:contacts 
                            {file? : The CSV file path to import}
                            {--batch-folder= : Import all CSV files from a folder}
                            {--no-header : Indicates that CSV files have no header row}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import contacts from CSV file(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $batchFolder = $this->option('batch-folder');

        if ($batchFolder) {
            return $this->handleBatchImport($batchFolder);
        }

        if (! $filePath) {
            $this->error('Either provide a file argument or use --batch-folder option');

            return self::FAILURE;
        }

        return $this->importFile($filePath);
    }

    /**
     * Handle batch import from a folder.
     */
    private function handleBatchImport(string $folderPath): int
    {
        if (! is_dir($folderPath)) {
            $this->error("Folder not found: {$folderPath}");

            return self::FAILURE;
        }

        $csvFiles = glob($folderPath.'/*.csv');
        if ($csvFiles === [] || $csvFiles === false) {
            $this->error("No CSV files found in: {$folderPath}");

            return self::FAILURE;
        }

        // Sort files to process part_0 first (with headers), then others
        usort($csvFiles, function ($a, $b): int {
            if (str_contains($a, 'part_0')) {
                return -1;
            }
            if (str_contains($b, 'part_0')) {
                return 1;
            }

            return strcmp($a, $b);
        });

        $this->info('Found '.count($csvFiles).' CSV files to process');

        foreach ($csvFiles as $index => $file) {
            $this->info("\n".str_repeat('=', 50));
            $this->info('Processing file '.($index + 1).'/'.count($csvFiles).': '.basename($file));
            $this->info(str_repeat('=', 50));

            $result = $this->importFile($file);
            if ($result === self::FAILURE) {
                $this->error('Failed to import: '.basename($file));

                return self::FAILURE;
            }
        }

        $this->info("\n".str_repeat('=', 50));
        $this->info('🎉 Batch import completed!');
        $this->info('📁 Files processed: '.count($csvFiles));
        $this->info(str_repeat('=', 50));

        return self::SUCCESS;
    }

    /**
     * Import a single CSV file.
     */
    private function importFile(string $filePath): int
    {
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $hasHeaders = ! $this->option('no-header') && str_contains($filePath, 'part_0');

        $this->info("Starting contact import from: {$filePath}");
        if (! $hasHeaders) {
            $this->warn('Processing file without headers');
        }

        try {
            $csv = Reader::createFromPath($filePath, 'r');

            if ($hasHeaders) {
                $csv->setHeaderOffset(0);
            }

            $records = iterator_to_array($csv->getRecords());
            $totalRecords = count($records);

            $this->info("Found {$totalRecords} records to import");

            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $skipReasons = [
                'missing_name' => 0,
                'duplicate_name' => 0,
                'duplicate_id_number' => 0,
                'database_error' => 0,
            ];

            DB::transaction(function () use ($records, $progressBar, &$imported, &$skipped, &$errors, &$skipReasons, $hasHeaders): void {
                foreach ($records as $index => $record) {
                    try {
                        $contactData = $hasHeaders
                            ? $this->mapCsvRecord($record)
                            : $this->mapCsvRecordWithoutHeaders($record);

                        if (empty($contactData['name'])) {
                            $skipped++;
                            $skipReasons['missing_name']++;
                            $errors[] = 'Row '.($index + 2).': Missing required name field';

                            continue;
                        }

                        // Check if contact already exists by name
                        $existingContactByName = Contact::query()->where('name', $contactData['name'])->first();

                        if ($existingContactByName) {
                            $skipped++;
                            $skipReasons['duplicate_name']++;
                            $errors[] = 'Row '.($index + 2).": Duplicate name '{$contactData['name']}'";

                            continue;
                        }

                        // Check if contact already exists by identification number (if provided)
                        if (! empty($contactData['identification_number'])) {
                            $existingContactByIdNumber = Contact::query()->where('identification_number', $contactData['identification_number'])->first();

                            if ($existingContactByIdNumber) {
                                $skipped++;
                                $skipReasons['duplicate_id_number']++;
                                $errors[] = 'Row '.($index + 2).": Duplicate identification number '{$contactData['identification_number']}'";

                                continue;
                            }
                        }

                        Contact::query()->create($contactData);
                        $imported++;
                    } catch (Exception $e) {
                        $skipped++;
                        $skipReasons['database_error']++;
                        $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
                    }

                    $progressBar->advance();
                }
            });

            $progressBar->finish();
            $this->newLine();

            $this->info('Import completed!');
            $this->info("✓ Imported: {$imported}");
            $this->info("⚠ Skipped: {$skipped}");

            if ($skipped > 0) {
                $this->newLine();
                $this->warn('Skip reasons breakdown:');
                if ($skipReasons['missing_name'] > 0) {
                    $this->error("  • Missing name: {$skipReasons['missing_name']}");
                }
                if ($skipReasons['duplicate_name'] > 0) {
                    $this->error("  • Duplicate name: {$skipReasons['duplicate_name']}");
                }
                if ($skipReasons['duplicate_id_number'] > 0) {
                    $this->error("  • Duplicate ID number: {$skipReasons['duplicate_id_number']}");
                }
                if ($skipReasons['database_error'] > 0) {
                    $this->error("  • Database errors: {$skipReasons['database_error']}");
                }
            }

            if ($errors !== [] && count($errors) <= 20) {
                $this->newLine();
                $this->warn('Detailed errors:');
                foreach ($errors as $error) {
                    $this->line($error);
                }
            } elseif ($errors !== []) {
                $this->newLine();
                $this->warn('First 10 detailed errors:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line($error);
                }
                $this->warn('... and '.(count($errors) - 10).' more errors');
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Map CSV record to contact data array.
     *
     * @param  array<string, string>  $record
     * @return array<string, mixed>
     */
    private function mapCsvRecord(array $record): array
    {
        $identificationType = $this->mapIdentificationType($record['Tipo de identificación'] ?? '');

        return [
            'name' => mb_trim($record['Nombre/Razón social'] ?? ''),
            'identification_type' => $identificationType,
            'identification_number' => $this->cleanIdentificationNumber($record['RNC/Cédula'] ?? ''),
            'phone_primary' => $this->cleanPhoneNumber($record['Teléfono 1'] ?? ''),
            'phone_secondary' => $this->cleanPhoneNumber($record['Teléfono 2'] ?? ''),
            'fax' => $this->cleanPhoneNumber($record['Fax'] ?? ''),
            'mobile' => $this->cleanPhoneNumber($record['Celular'] ?? ''),
            'email' => $this->cleanEmail($record['Correo'] ?? ''),
            'contact_type' => ContactType::Customer->value,
            'status' => 'active',
            'credit_limit' => $this->parseCreditLimit($record['Límite de crédito'] ?? ''),
        ];
    }

    /**
     * Map CSV record without headers to contact data array.
     * Based on the CSV structure: Name, ID Type, ID Number, Phone1, Phone2, Fax, Mobile, Address, Province, Municipality, Country, Email, Credit Limit, ...
     *
     * @param  array<int, string>  $record
     * @return array<string, mixed>
     */
    private function mapCsvRecordWithoutHeaders(array $record): array
    {
        // Convert indexed array to expected format
        $mappedRecord = [
            'name' => mb_trim($record[0] ?? ''),
            'identification_type' => $record[1] ?? '',
            'identification_number' => $record[2] ?? '',
            'phone_primary' => $record[3] ?? '',
            'phone_secondary' => $record[4] ?? '',
            'fax' => $record[5] ?? '',
            'mobile' => $record[6] ?? '',
            'address' => $record[7] ?? '',
            'province' => $record[8] ?? '',
            'municipality' => $record[9] ?? '',
            'country' => $record[10] ?? '',
            'email' => $record[11] ?? '',
            'credit_limit' => $record[12] ?? '',
        ];

        $identificationType = $this->mapIdentificationType($mappedRecord['identification_type']);

        return [
            'name' => $mappedRecord['name'],
            'identification_type' => $identificationType,
            'identification_number' => $this->cleanIdentificationNumber($mappedRecord['identification_number']),
            'phone_primary' => $this->cleanPhoneNumber($mappedRecord['phone_primary']),
            'phone_secondary' => $this->cleanPhoneNumber($mappedRecord['phone_secondary']),
            'fax' => $this->cleanPhoneNumber($mappedRecord['fax']),
            'mobile' => $this->cleanPhoneNumber($mappedRecord['mobile']),
            'email' => $this->cleanEmail($mappedRecord['email']),
            'contact_type' => ContactType::Customer->value,
            'status' => 'active',
            'credit_limit' => $this->parseCreditLimit($mappedRecord['credit_limit']),
        ];
    }

    /**
     * Map CSV identification type to enum value.
     */
    private function mapIdentificationType(string $type): ?string
    {
        $type = mb_strtolower(mb_trim($type));

        return match ($type) {
            'cédula', 'cedula' => IdentificationType::Cedula->value,
            'rnc' => IdentificationType::RNC->value,
            'pasaporte' => IdentificationType::Pasaporte->value,
            default => null,
        };
    }

    /**
     * Clean identification number.
     */
    private function cleanIdentificationNumber(string $number): ?string
    {
        $number = mb_trim($number);

        return $number === '' || $number === '0' ? null : $number;
    }

    /**
     * Clean and validate phone number.
     */
    private function cleanPhoneNumber(string $phone): ?string
    {
        $phone = mb_trim($phone);
        if ($phone === '' || $phone === '0') {
            return null;
        }

        // Remove non-numeric characters except + for international numbers
        $phone = preg_replace('/[^\d+]/', '', $phone);

        return empty($phone) ? null : $phone;
    }

    /**
     * Clean and validate email.
     */
    private function cleanEmail(string $email): ?string
    {
        $email = mb_trim($email);
        if ($email === '' || $email === '0') {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Parse credit limit from CSV.
     */
    private function parseCreditLimit(string $creditLimit): float
    {
        if ($creditLimit === '' || $creditLimit === '0') {
            return 0.0;
        }

        // Remove currency symbols and commas
        $creditLimit = preg_replace('/[^\d.]/', '', $creditLimit);

        return (float) $creditLimit;
    }
}
