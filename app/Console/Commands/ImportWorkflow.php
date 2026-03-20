<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\Prescription;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use JsonException;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

final class ImportWorkflow extends Command
{
    use HasATenantsOption, TenantAwareCommand;

    private const IMPORT_RESULT_IMPORTED = 'imported';

    private const IMPORT_RESULT_MISSING_WORKSPACE = 'missing_workspace';

    private const IMPORT_RESULT_MISSING_CONTACT = 'missing_contact';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:workflows
                            {file? : The JSON file path to import. Defaults to the tenant procesos.json file}
                            {--limit=1000000 : Number of workflows to import}
                            {--offset=0 : Number of workflows to skip before importing}
                            {--debug : Show import diagnostics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import workflows from JSON file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->resolveFilePath();
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $debug = (bool) $this->option('debug');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Empiezando la importacion de workflows desde el archivo JSON: {$filePath}");
        $this->info("Limite: {$limit} workflows");
        $this->info("Saltando: {$offset} workflows");

        try {
            $records = $this->readJsonRecords($filePath);

            if ($debug) {
                $this->warn('Debug de importacion habilitado.');

                if ($records !== []) {
                    $firstRecord = $this->cleanUtf8Record($records[array_key_first($records)]);

                    $this->line('Campos detectados: '.implode(', ', array_slice(array_keys($firstRecord), 0, 8)));
                    $this->line('Total campos detectados: '.count($firstRecord));
                }
            }

            $this->info('Encontrados totales en el archivo JSON: '.count($records));

            $records = array_slice($records, $offset, $limit);
            $this->info('Procesando un total de workflows: '.count($records));

            $progressBar = $this->output->createProgressBar(count($records));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $skipReasons = [];

            foreach ($records as $index => $record) {
                try {
                    $cleanRecord = $this->cleanUtf8Record($record);
                    $result = $this->importWorkflowRow($cleanRecord);

                    if ($result === self::IMPORT_RESULT_IMPORTED) {
                        $imported++;
                    } else {
                        $skipped++;
                        $skipReasons[$result] = ($skipReasons[$result] ?? 0) + 1;
                    }
                } catch (Exception $e) {
                    $skipped++;
                    $skipReasons['exception'] = ($skipReasons['exception'] ?? 0) + 1;
                    $errors[] = 'Row '.($offset + $index + 1).': '.$e->getMessage();
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            $this->info('Importacion completada.');
            $this->info("Importadas: {$imported}");
            $this->info("Omitidas: {$skipped}");

            if ($debug && $skipReasons !== []) {
                $this->newLine();
                $this->warn('Resumen de omisiones:');

                foreach ($skipReasons as $reason => $count) {
                    $this->line('- '.$this->describeSkipReason($reason).": {$count}");
                }
            }

            if ($errors !== []) {
                $this->newLine();
                $this->warn('Errores encontrados:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->error($error);
                }

                if (count($errors) > 10) {
                    $this->warn('... y '.(count($errors) - 10).' errores mas');
                }
            }

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importWorkflowRow(array $row): string
    {
        return match ($row['TYPE'] ?? '') {
            'Elaboración de lentes' => $this->importarElaboracion($row),
            default => self::IMPORT_RESULT_MISSING_WORKSPACE,
        };
    }

    private function importarElaboracion(array $row): string
    {
        $id = '019bc39a-fab8-7005-9f00-645f6b275497';

        $estado = $row['Estado'] ?? '';

        $workflow_id = match ($row['CODIGO_SUCURSAL'] ?? null) {
            'Ten001' => 2,
            'Sal001' => 3,
            'Cv001' => 1,
            'Op001' => 4,
            default => throw new Exception('Sucursal no reconocida: '.($row['CODIGO_SUCURSAL'] ?? 'null')),
        };

        $contactName = $this->cleanUtf8String(mb_trim($row['CLIENT'] ?? ''));

        if ($contactName === '') {
            return self::IMPORT_RESULT_MISSING_CONTACT;
        }

        $contactNameKey = $this->normalizeNameKey($contactName);
        $contact = Contact::query()
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$contactNameKey])
            ->first();

        if (! $contact) {
            $contact = Contact::query()
                ->where('name', 'LIKE', "%{$contactName}%")
                ->first();
        }

        if (! $contact) {
            $contact = Contact::query()->create([
                'name' => $contactName,
                'contact_type' => ContactType::Customer,
                'identification_type' => IdentificationType::Cedula,
                'identification_number' => $row['CLIENT_IDENTIFICATION'] ?? null,
                'email' => null,
                'phone_primary' => $row['CLIENT_PHONE'] ?? null,
                'status' => 'active',
                'credit_limit' => 0.00,
            ]);

            $this->line("Creado contacto faltante: {$contactName}");
        }

        $stage = WorkflowStage::query()
            ->where('name', $row['Estado'] ?? '')
            ->where('workflow_id', $id)
            ->first();

        $prescription = Prescription::query()
            ->withoutGlobalScopes()
            ->where('legacy_prescription_id', $row['prescription_id'] ?? '')
            ->first();

        $tabla = Mastertable::query()
            ->where('alias', 'laboratorios')
            ->first();
    if (! $tabla) {
                dd(tenant()->id);
            }

    $tablaCristal = Mastertable::query()
                ->where('alias', 'cristales_a_vender')
                ->first();

    $cristal = $tablaCristal->items()
                ->where('name', $row['CRISTAL'] ?? '')
                ->first();

                 if (! $tablaCristal) {
                dd(tenant()->id);
            }

    if (! $cristal) {
        $cristal = MastertableItem::query()
            ->create([
                'mastertable_id' => $tablaCristal->id,
                'name' => $row['CRISTAL'] ?? '',
            ]);

        $this->line("Creado cristal faltante: {$row['CRISTAL']}");
    }


    $laboratorio = $tabla->items()
            ->where('name', $row['Laboratorio'] ?? '')
            ->first();

        if (! $laboratorio) {
            $laboratorio = MastertableItem::query()
                ->create([
                    'mastertable_id' => $tabla->id,
                    'name' => $row['Laboratorio'],
                ]);

            $this->line("Creado laboratorio faltante: {$row['Laboratorio']}");
        }

        $metadata = [
            'laboratorio' => (string) $laboratorio->id,
            'cristal_a_realizar' => (string) $cristal->id,
        ];

        if (! $stage) {
            throw new Exception('Estado de workflow no reconocido: '.($row['Estado'] ?? 'null'));
        }

        $data = [
            'workspace_id' => $workflow_id,
            'workflow_id' => $id,
            'workflow_stage_id' => $stage?->id,
            'contact_id' => $contact->id,
            'prescription_id' => $prescription?->id,
            'priority' => 'medium',
            'due_date' => $this->parseDateTime($row['updated_at'] ?? ''),
            'completed_at' => $row['Estado'] === 'Entregado' ? $this->parseDateTime($row['updated_at'] ?? '') : null,
            'metadata' => $metadata,
        ];

        WorkflowJob::query()->create($data);

        return self::IMPORT_RESULT_IMPORTED;
    }

    private function resolveFilePath(): string
    {
        $filePath = $this->argument('file');

        if (is_string($filePath) && $filePath !== '') {
            return $filePath;
        }

        $tenantId = tenant()?->id;

        if (! is_string($tenantId) || $tenantId === '') {
            return storage_path('app/procesos.json');
        }

        return storage_path('tenant'.$tenantId.'/app/procesos.json');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonRecords(string $filePath): array
    {
        try {
            $contents = file_get_contents($filePath);

            if ($contents === false) {
                throw new Exception('No se pudo leer el archivo JSON.');
            }

            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new Exception('El archivo JSON no es valido: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new Exception('El archivo JSON debe contener un arreglo en el nivel superior.');
        }

        foreach ($decoded as $index => $record) {
            if (! is_array($record)) {
                throw new Exception('Cada workflow del archivo JSON debe ser un objeto. Error en indice '.$index.'.');
            }
        }

        /** @var list<array<string, mixed>> $decoded */
        return array_values($decoded);
    }

    private function describeSkipReason(string $reason): string
    {
        return match ($reason) {
            self::IMPORT_RESULT_MISSING_WORKSPACE => 'sin sucursal o codigo de sucursal',
            self::IMPORT_RESULT_MISSING_CONTACT => 'sin nombre de cliente',
            'exception' => 'error durante la importacion',
            default => $reason,
        };
    }

    private function normalizeNameKey(string $value): string
    {
        $normalized = mb_strtolower(mb_trim($value));
        $normalized = str_replace(["\0", "\x0B"], '', $normalized);

        return preg_replace('/\s+/', '', $normalized) ?? '';
    }

    private function parseDateTime(string $value): ?Carbon
    {
        $value = $this->cleanUtf8String(mb_trim($value));
        if ($value === '' || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Exception) {
            return null;
        }
    }

    private function cleanNullable(string $value): ?string
    {
        $cleaned = $this->cleanUtf8String(mb_trim($value));

        if ($cleaned === '' || mb_strtoupper($cleaned) === 'N/A') {
            return null;
        }

        return $cleaned;
    }

    private function normalizeSphereValue(string $value): ?string
    {
        $cleaned = $this->cleanNullable($value);
        if ($cleaned === null) {
            return null;
        }

        $normalized = str_replace(',', '.', $cleaned);
        $normalized = preg_replace('/\s+/', '', $normalized);

        if (preg_match('/[-+]?\d+(?:\.\d+)?/', $normalized, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    /**
     * Clean UTF-8 characters from a JSON record.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, string>
     */
    private function cleanUtf8Record(array $record): array
    {
        $cleanRecord = [];

        foreach ($record as $key => $value) {
            $cleanRecord[$this->cleanUtf8String((string) $key)] = $this->cleanUtf8Value($value);
        }

        return $cleanRecord;
    }

    private function cleanUtf8Value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return $this->cleanUtf8String((string) $value);
        }

        return $this->cleanUtf8String(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
    }

    /**
     * Clean UTF-8 characters from a string.
     */
    private function cleanUtf8String(string $str): string
    {
        $str = str_replace(["\0", "\x0B"], '', $str);

        if (! mb_check_encoding($str, 'UTF-8')) {
            $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encoding);
            } else {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            }
        }

        $str = filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        return mb_trim($str);
    }
}
