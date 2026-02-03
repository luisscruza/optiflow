<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactType;
use App\Enums\Gender;
use App\Models\ContactImport;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ProcessContactImportAction
{
    public function __construct(
        private ParseCsvFileAction $parseCsvFileAction,
        private ValidateContactImportDataAction $validateContactImportDataAction,
        private CreateContactAction $createContactAction
    ) {}

    /**
     * Process the contact import.
     */
    public function handle(ContactImport $import, User $user): array
    {
        if (! $import->column_mapping) {
            throw new InvalidArgumentException('Column mapping is required');
        }

        $import->markAsProcessing();

        return DB::transaction(function () use ($import, $user): array {
            $importedContacts = [];
            $processingErrors = [];
            $successful = 0;
            $errors = 0;
            $validationErrors = [];
            $rowOffset = 0;
            $headers = $import->headers;

            $sourceFiles = $import->source_files ?? [
                [
                    'file_path' => $import->file_path,
                    'has_header' => true,
                ],
            ];

            foreach ($sourceFiles as $fileIndex => $sourceFile) {
                $parseResult = $this->parseCsvFileAction->handle(
                    $import,
                    PHP_INT_MAX,
                    $headers,
                    (bool) ($sourceFile['has_header'] ?? $fileIndex === 0),
                    $sourceFile['file_path'] ?? $import->file_path
                );

                if ($headers === null) {
                    $headers = $parseResult['headers'];
                }

                $validationResult = $this->validateContactImportDataAction->handle(
                    $import,
                    $parseResult['data'],
                    $rowOffset
                );

                foreach ($validationResult['valid'] as $validRow) {
                    try {
                        $contact = $this->createContact($user, $validRow['data']);
                        $importedContacts[] = $contact->id;
                        $successful++;
                    } catch (Exception $exception) {
                        $errors++;
                        $processingErrors[] = [
                            'row' => $validRow['row'],
                            'field' => 'general',
                            'message' => $exception->getMessage(),
                        ];
                    }
                }

                $validationErrors = array_merge($validationErrors, $validationResult['errors']);
                $rowOffset += $parseResult['total_rows'];
            }

            $errors += count($validationErrors);
            $totalProcessed = $successful + $errors;

            $import->updateProgress($totalProcessed, $successful, $errors);

            $summary = [
                'imported' => $successful,
                'errors' => $errors,
                'validation_errors' => count($validationErrors),
                'processing_errors' => count($processingErrors),
                'imported_contact_ids' => $importedContacts,
            ];

            $import->update([
                'import_summary' => $summary,
                'validation_errors' => array_merge($validationErrors, $processingErrors),
            ]);

            if ($errors === 0) {
                $import->markAsCompleted();
            } else {
                $import->markAsFailed();
            }

            return $summary;
        });
    }

    /**
     * Create a contact from row data.
     *
     * @param  array<string, mixed>  $rowData
     */
    private function createContact(User $user, array $rowData): \App\Models\Contact
    {
        $contactType = ContactType::Customer;
        $gender = $rowData['gender'] ?? Gender::NotSpecified->value;

        $data = [
            'name' => $rowData['name'],
            'email' => $rowData['email'] ?? null,
            'phone_primary' => $rowData['phone_primary'] ?? null,
            'phone_secondary' => $rowData['phone_secondary'] ?? null,
            'mobile' => $rowData['mobile'] ?? null,
            'fax' => $rowData['fax'] ?? null,
            'identification_type' => $rowData['identification_type'] ?? null,
            'identification_number' => $rowData['identification_number'] ?? null,
            'contact_type' => $contactType,
            'status' => $rowData['status'] ?? 'active',
            'observations' => $rowData['observations'] ?? null,
            'credit_limit' => $rowData['credit_limit'] ?? 0,
            'metadata' => $rowData['metadata'] ?? null,
            'birth_date' => $rowData['birth_date'] ?? null,
            'gender' => $gender,
            'created_at' => $rowData['created_at'] ?? null,
            'updated_at' => $rowData['updated_at'] ?? null,
        ];

        return $this->createContactAction->handle($user, $data);
    }
}
