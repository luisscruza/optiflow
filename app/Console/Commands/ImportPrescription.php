<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreateWorkspaceAction;
use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

final class ImportPrescription extends Command
{
    use HasATenantsOption, TenantAwareCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:prescriptions {file : The CSV file path to import} {--limit=1000000 : Number of prescriptions to import} {--offset=0 : Number of prescriptions to skip before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import prescriptions from CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info("Empiezando la importacion de recetas desde el archivo: {$filePath}");
        $this->info("Limite: {$limit} recetas");
        $this->info("Saltando: {$offset} recetas");

        DB::table('prescriptions')->delete();

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setDelimiter(';');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $this->info('Encontradas totales en el archivo CSV: '.count($records));

            $records = array_slice($records, $offset, $limit);
            $this->info('Procesando un total de recetas: '.count($records));

            $createdBy = $this->resolveCreatedBy();
            if (! $createdBy) {
                $this->error('No se encontro un usuario para asignar como creador.');

                return self::FAILURE;
            }

            $progressBar = $this->output->createProgressBar(count($records));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                try {
                    $cleanRecord = $this->cleanUtf8Record($record);
                    if ($this->importPrescriptionRow($cleanRecord, $createdBy)) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                } catch (Exception $e) {
                    $skipped++;
                    $errors[] = 'Row '.($offset + $index + 1).': '.$e->getMessage();
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            $this->info('Importacion completada.');
            $this->info("Importadas: {$imported}");
            $this->info("Omitidas: {$skipped}");

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
    private function importPrescriptionRow(array $row, User $createdBy): bool
    {
        $workspaceCode = $this->cleanUtf8String(mb_trim($row['CODIGO_SUCURSAL'] ?? ''));
        $workspaceName = $this->cleanUtf8String(mb_trim($row['Sucursal'] ?? ''));

        if ($workspaceCode === '' && $workspaceName === '') {
            return false;
        }

        $workspace = $this->resolveWorkspace($workspaceCode, $workspaceName, $createdBy);

        $contactName = $this->cleanUtf8String(mb_trim($row['Cliente'] ?? ''));
        if ($contactName === '') {
            return false;
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
                'identification_number' => null,
                'email' => null,
                'phone_primary' => null,
                'status' => 'active',
                'credit_limit' => 0.00,
            ]);

            $this->line("Creado contacto faltante: {$contactName}");
        }

        $optometrist = $this->resolveOptometrist($row['Examinador'] ?? '');

        $createdAt = $this->parseDateTime($row['created_at'] ?? '');
        $updatedAt = $this->parseDateTime($row['updated_at'] ?? '');

        $prescriptionData = [
            'workspace_id' => $workspace->id,
            'patient_id' => $contact->id,
            'created_by' => $createdBy->id,
            'optometrist_id' => $optometrist?->id,
            'refraccion_od_esfera' => $this->normalizeSphereValue($row['esfera_od'] ?? ''),
            'refraccion_oi_esfera' => $this->normalizeSphereValue($row['esfera_oi'] ?? ''),
            'refraccion_od_cilindro' => $this->normalizeSphereValue($row['cilindro_od'] ?? ''),
            'refraccion_oi_cilindro' => $this->normalizeSphereValue($row['cilindro_oi'] ?? ''),
            'refraccion_od_eje' => $this->normalizeSphereValue($row['eje_od'] ?? ''),
            'refraccion_oi_eje' => $this->normalizeSphereValue($row['eje_oi'] ?? ''),
            'refraccion_subjetivo_od_adicion' => $this->normalizeSphereValue($row['adicion_od'] ?? ''),
            'refraccion_subjetivo_oi_adicion' => $this->normalizeSphereValue($row['adicion_oi'] ?? ''),
            'subjetivo_od_esfera' => $this->normalizeSphereValue($row['esfera_od'] ?? ''),
            'subjetivo_oi_esfera' => $this->normalizeSphereValue($row['esfera_oi'] ?? ''),
            'subjetivo_od_cilindro' => $this->normalizeSphereValue($row['cilindro_od'] ?? ''),
            'subjetivo_oi_cilindro' => $this->normalizeSphereValue($row['cilindro_oi'] ?? ''),
            'subjetivo_od_eje' => $this->normalizeSphereValue($row['eje_od'] ?? ''),
            'subjetivo_oi_eje' => $this->normalizeSphereValue($row['eje_oi'] ?? ''),
            'subjetivo_od_add' => $this->normalizeSphereValue($row['adicion_od'] ?? ''),
            'subjetivo_oi_add' => $this->normalizeSphereValue($row['adicion_oi'] ?? ''),
            'subjetivo_od_av_lejos' => $this->cleanNullable($row['agudeza_visual'] ?? ''),
            'subjetivo_oi_av_lejos' => $this->cleanNullable($row['agudeza_visual'] ?? ''),
            'observaciones' => $this->cleanNullable($row['Observaciones generales'] ?? ''),
            'observaciones_internas' => $this->cleanNullable($row['comentario'] ?? ''),
        ];

        if ($createdAt) {
            $prescriptionData['created_at'] = $createdAt;
        }

        if ($updatedAt) {
            $prescriptionData['updated_at'] = $updatedAt;
        }

        $prescription = Prescription::query()->create($prescriptionData);

        $this->attachMastertableItems($prescription, 'tipos_de_lentes', $row['Lente recomendado'] ?? '');
        $this->attachMastertableItems($prescription, 'tipos_de_gotas', $row['Gota recomendada'] ?? '');
        $this->attachMastertableItems($prescription, 'tipos_de_montura', $row['Montura recomendada'] ?? '');
        $this->attachMastertableItems($prescription, 'estado_salud_actual', $row['estado_actual'] ?? '');
        $this->attachMastertableItems($prescription, 'historia_ocular_familiar', $row['historia_ocular'] ?? '');
        $this->attachMastertableItems($prescription, 'canales_de_referimiento', $row['Canal'] ?? '');

        return true;
    }

    private function resolveOptometrist(string $value): ?Contact
    {
        $name = $this->cleanUtf8String(mb_trim($value));
        if ($name === '') {
            return null;
        }

        $nameKey = $this->normalizeNameKey($name);
        if ($nameKey === '') {
            return null;
        }

        $optometrist = Contact::query()
            ->where('contact_type', ContactType::Optometrist->value)
            ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$nameKey])
            ->first();

        if (! $optometrist) {
            $optometrist = Contact::query()
                ->where('contact_type', ContactType::Optometrist->value)
                ->where('name', 'LIKE', "%{$name}%")
                ->first();
        }

        if ($optometrist) {
            return $optometrist;
        }

        $optometrist = Contact::query()->create([
            'name' => $name,
            'contact_type' => ContactType::Optometrist,
            'identification_type' => IdentificationType::Cedula,
            'identification_number' => null,
            'email' => null,
            'phone_primary' => null,
            'status' => 'active',
            'credit_limit' => 0.00,
        ]);

        $this->line("Creado optometrista faltante: {$name}");

        return $optometrist;
    }

    private function normalizeNameKey(string $value): string
    {
        $normalized = mb_strtolower(mb_trim($value));
        $normalized = str_replace(["\0", "\x0B"], '', $normalized);

        return preg_replace('/\s+/', '', $normalized) ?? '';
    }

    private function attachMastertableItems(Prescription $prescription, string $alias, string $rawValue): void
    {
        $values = $this->parseMastertableValues($rawValue);
        if ($values === []) {
            return;
        }

        $mastertable = Mastertable::query()->where('alias', $alias)->first();
        if (! $mastertable) {
            return;
        }

        foreach ($values as $value) {
            $itemKey = $this->normalizeNameKey($value);
            if ($itemKey === '') {
                continue;
            }

            $item = MastertableItem::query()
                ->where('mastertable_id', $mastertable->id)
                ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$itemKey])
                ->first();

            if (! $item) {
                $item = MastertableItem::query()
                    ->where('mastertable_id', $mastertable->id)
                    ->where('name', 'LIKE', "%{$value}%")
                    ->first();
            }

            if (! $item) {
                $item = MastertableItem::query()->create([
                    'mastertable_id' => $mastertable->id,
                    'name' => $value,
                ]);
            }

            $prescription->belongsToMany(
                related: MastertableItem::class,
                table: 'prescription_item',
                foreignPivotKey: 'prescription_id',
                relatedPivotKey: 'mastertable_item_id'
            )->syncWithoutDetaching([
                $item->id => ['mastertable_alias' => $alias],
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseMastertableValues(string $rawValue): array
    {
        $cleaned = mb_trim($rawValue);
        $cleaned = str_replace(["\0", "\x0B"], '', $cleaned);
        if ($cleaned === '' || $cleaned === '[]' || mb_strtoupper($cleaned) === 'N/A') {
            return [];
        }

        $cleaned = str_replace(["\r", "\n"], ',', $cleaned);
        $parts = array_map('trim', explode(',', $cleaned));

        $values = [];
        foreach ($parts as $part) {
            if ($part === '' || mb_strtoupper($part) === 'N/A') {
                continue;
            }

            $values[] = $part;
        }

        return array_values(array_unique($values));
    }

    private function resolveCreatedBy(): ?User
    {
        return User::query()->where('business_role', 'admin')->first()
            ?? User::query()->first();
    }

    private function resolveWorkspace(string $workspaceCode, string $workspaceName, User $createdBy): Workspace
    {
        if ($workspaceCode !== '') {
            $workspace = Workspace::query()->where('code', $workspaceCode)->first();
            if ($workspace) {
                return $workspace;
            }
        }

        if ($workspaceName !== '') {
            $workspace = Workspace::query()->where('name', $workspaceName)->first();
            if ($workspace) {
                return $workspace;
            }
        }

        $name = $workspaceName !== '' ? $workspaceName : ($workspaceCode !== '' ? $workspaceCode : 'Workspace');
        $code = $workspaceCode !== '' ? $workspaceCode : null;

        return app(CreateWorkspaceAction::class)->handle($createdBy, [
            'name' => $name,
            'code' => $code ?? Str::lower(Str::slug($name)),
            'description' => $name,
        ]);
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
     * Clean UTF-8 characters from a CSV record.
     *
     * @param  array<string, string>  $record
     * @return array<string, string>
     */
    private function cleanUtf8Record(array $record): array
    {
        $cleanRecord = [];

        foreach ($record as $key => $value) {
            $cleanRecord[$this->cleanUtf8String($key)] = $this->cleanUtf8String($value);
        }

        return $cleanRecord;
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
