<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContactType;
use App\Enums\Gender;
use App\Enums\IdentificationType;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JsonException;
use RuntimeException;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;
use Throwable;

final class UpdateContact extends Command
{
    use HasATenantsOption, TenantAwareCommand;

    private const RESULT_CREATED = 'created';

    private const RESULT_UPDATED = 'updated';

    private const RESULT_UNCHANGED = 'unchanged';

    private const RESULT_SKIPPED = 'skipped';

    /**
     * @var string
     */
    protected $signature = 'contacts:update-from-json
                            {file? : The JSON file path to import. Defaults to the tenant clients.json file}
                            {--limit=1000000 : Number of contacts to process}
                            {--offset=0 : Number of contacts to skip before processing}
                            {--debug : Show processing diagnostics}';

    /**
     * @var string
     */
    protected $description = 'Create or complete contacts from a legacy JSON export';

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

        try {
            $records = $this->readJsonRecords($filePath);
        } catch (Throwable $exception) {
            $this->error('Import failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Empiezando actualizacion de contactos desde: {$filePath}");
        $this->info('Contactos encontrados en JSON: '.count($records));

        $records = array_slice($records, $offset, $limit);

        $this->info('Procesando contactos: '.count($records));

        $progressBar = $this->output->createProgressBar(count($records));
        $progressBar->start();

        $stats = [
            self::RESULT_CREATED => 0,
            self::RESULT_UPDATED => 0,
            self::RESULT_UNCHANGED => 0,
            self::RESULT_SKIPPED => 0,
        ];

        $skipReasons = [];
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                $result = $this->processRecord($this->cleanUtf8Record($record), $debug, $skipReasons);
                $stats[$result]++;
            } catch (Throwable $exception) {
                $stats[self::RESULT_SKIPPED]++;
                $skipReasons['exception'] = ($skipReasons['exception'] ?? 0) + 1;
                $errors[] = 'Row '.($offset + $index + 1).': '.$exception->getMessage();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Actualizacion completada.');
        $this->info('Creados: '.$stats[self::RESULT_CREATED]);
        $this->info('Actualizados: '.$stats[self::RESULT_UPDATED]);
        $this->info('Sin cambios: '.$stats[self::RESULT_UNCHANGED]);
        $this->info('Omitidos: '.$stats[self::RESULT_SKIPPED]);

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
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, int>  $skipReasons
     */
    private function processRecord(array $row, bool $debug, array &$skipReasons): string
    {
        $payload = $this->mapRowToContactPayload($row);

        if ($payload['name'] === null) {
            $skipReasons['missing_name'] = ($skipReasons['missing_name'] ?? 0) + 1;

            return self::RESULT_SKIPPED;
        }

        $match = $this->findMatchingContact($payload);

        if ($match['ambiguous']) {
            $skipReasons['ambiguous_match'] = ($skipReasons['ambiguous_match'] ?? 0) + 1;

            if ($debug) {
                $this->warn('Contacto omitido por coincidencia ambigua: '.$payload['name']);
            }

            return self::RESULT_SKIPPED;
        }

        $contact = $match['contact'];

        if (! $contact instanceof Contact) {
            Contact::query()->create($this->buildCreatePayload($payload));

            if ($debug) {
                $this->line('Creado contacto: '.$payload['name']);
            }

            return self::RESULT_CREATED;
        }

        $updates = $this->buildUpdatePayload($contact, $payload);

        if ($updates === []) {
            return self::RESULT_UNCHANGED;
        }

        $contact->update($updates);

        if ($debug) {
            $this->line("Actualizado contacto #{$contact->id}: {$contact->name}");
        }

        return self::RESULT_UPDATED;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{
     *     legacy_id: int|null,
     *     name: string|null,
     *     email: string|null,
     *     phone_primary: string|null,
     *     phone_secondary: string|null,
     *     identification_number: string|null,
     *     identification_type: string|null,
     *     birth_date: CarbonImmutable|null,
     *     gender: string|null,
     *     observations: string|null,
     *     metadata: array<string, mixed>
     * }
     */
    private function mapRowToContactPayload(array $row): array
    {
        $identificationNumber = $this->cleanNullable($row['identificacion'] ?? '');
        $legacyId = $this->parseInteger($row['id'] ?? '');
        $legacyBranch = $this->parseInteger($row['sucursal'] ?? '');
        $legacyAge = $this->parseInteger($row['age'] ?? '');

        $metadata = array_filter([
            'legacy_client_id' => $legacyId,
            'legacy_branch_id' => $legacyBranch,
            'legacy_age' => $legacyAge,
            'legacy_created_at' => $this->cleanNullable($row['created_at'] ?? ''),
            'legacy_updated_at' => $this->cleanNullable($row['updated_at'] ?? ''),
        ], fn (mixed $value): bool => $value !== null);

        return [
            'legacy_id' => $legacyId,
            'name' => $this->cleanNullable($row['name'] ?? ''),
            'email' => $this->cleanEmail($row['email'] ?? ''),
            'phone_primary' => $this->cleanPhoneNumber($row['f_phone'] ?? ''),
            'phone_secondary' => $this->cleanPhoneNumber($row['s_phone'] ?? ''),
            'identification_number' => $identificationNumber,
            'identification_type' => $identificationNumber !== null ? IdentificationType::Cedula->value : null,
            'birth_date' => $this->parseDate($row['birthdate'] ?? ''),
            'gender' => $this->parseGender($row['gender'] ?? ''),
            'observations' => $this->cleanNullable($row['comment'] ?? ''),
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{contact: Contact|null, ambiguous: bool}
     */
    private function findMatchingContact(array $payload): array
    {
        foreach (['identification_number', 'email', 'phone_primary', 'phone_secondary', 'name'] as $field) {
            $matches = $this->findCandidatesByPriority($payload, $field);

            if ($matches->isEmpty()) {
                continue;
            }

            if ($matches->count() > 1) {
                return [
                    'contact' => null,
                    'ambiguous' => true,
                ];
            }

            return [
                'contact' => $matches->first(),
                'ambiguous' => false,
            ];
        }

        return [
            'contact' => null,
            'ambiguous' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, Contact>
     */
    private function findCandidatesByPriority(array $payload, string $field): Collection
    {
        return match ($field) {
            'identification_number' => $this->findByIdentification($payload['identification_number']),
            'email' => $this->findByEmail($payload['email']),
            'phone_primary', 'phone_secondary' => $this->findByPhone($payload[$field]),
            'name' => $this->findByName($payload['name']),
            default => collect(),
        };
    }

    /**
     * @return Collection<int, Contact>
     */
    private function findByIdentification(?string $identificationNumber): Collection
    {
        if ($identificationNumber === null) {
            return collect();
        }

        $normalized = $this->normalizeIdentification($identificationNumber);

        return Contact::query()
            ->whereNotNull('identification_number')
            ->get()
            ->filter(fn (Contact $contact): bool => $this->normalizeIdentification($contact->identification_number) === $normalized)
            ->values();
    }

    /**
     * @return Collection<int, Contact>
     */
    private function findByEmail(?string $email): Collection
    {
        if ($email === null) {
            return collect();
        }

        return Contact::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->get()
            ->values();
    }

    /**
     * @return Collection<int, Contact>
     */
    private function findByPhone(?string $phone): Collection
    {
        if ($phone === null) {
            return collect();
        }

        $normalized = $this->normalizePhone($phone);

        return Contact::query()
            ->where(function ($query): void {
                $query->whereNotNull('phone_primary')
                    ->orWhereNotNull('phone_secondary')
                    ->orWhereNotNull('mobile')
                    ->orWhereNotNull('fax');
            })
            ->get()
            ->filter(function (Contact $contact) use ($normalized): bool {
                foreach ([$contact->phone_primary, $contact->phone_secondary, $contact->mobile, $contact->fax] as $candidate) {
                    if ($this->normalizePhone($candidate) === $normalized) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * @return Collection<int, Contact>
     */
    private function findByName(?string $name): Collection
    {
        if ($name === null) {
            return collect();
        }

        $normalized = $this->normalizeName($name);

        return Contact::query()
            ->whereNotNull('name')
            ->get()
            ->filter(fn (Contact $contact): bool => $this->normalizeName($contact->name) === $normalized)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildCreatePayload(array $payload): array
    {
        return [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone_primary' => $payload['phone_primary'],
            'phone_secondary' => $payload['phone_secondary'],
            'identification_type' => $payload['identification_type'],
            'identification_number' => $payload['identification_number'],
            'contact_type' => ContactType::Customer,
            'status' => 'active',
            'observations' => $payload['observations'],
            'credit_limit' => 0,
            'metadata' => $payload['metadata'] !== [] ? $payload['metadata'] : null,
            'birth_date' => $payload['birth_date'],
            'gender' => $payload['gender'] ?? Gender::NotSpecified->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(Contact $contact, array $payload): array
    {
        $updates = [];

        $this->fillIfEmpty($updates, 'email', $contact->email, $payload['email']);
        $this->fillIfEmpty($updates, 'phone_primary', $contact->phone_primary, $payload['phone_primary']);
        $this->fillIfEmpty($updates, 'phone_secondary', $contact->phone_secondary, $payload['phone_secondary']);
        $this->fillIfEmpty($updates, 'identification_number', $contact->identification_number, $payload['identification_number']);
        $this->fillIfEmpty($updates, 'observations', $contact->observations, $payload['observations']);

        if ($this->isEmptyValue($contact->identification_type) && $payload['identification_number'] !== null) {
            $updates['identification_type'] = IdentificationType::Cedula->value;
        }

        if ($contact->birth_date === null && $payload['birth_date'] instanceof CarbonImmutable) {
            $updates['birth_date'] = $payload['birth_date'];
        }

        if (($contact->gender === null || $contact->gender->value === Gender::NotSpecified->value) && $payload['gender'] !== null) {
            $updates['gender'] = $payload['gender'];
        }

        $metadata = $this->mergeMetadata($contact->metadata, $payload['metadata']);

        if ($metadata !== $contact->metadata) {
            $updates['metadata'] = $metadata;
        }

        return $updates;
    }

    /**
     * @param  array<string, mixed>  $updates
     */
    private function fillIfEmpty(array &$updates, string $field, mixed $currentValue, mixed $incomingValue): void
    {
        if ($this->isEmptyValue($currentValue) && ! $this->isEmptyValue($incomingValue)) {
            $updates[$field] = $incomingValue;
        }
    }

    /**
     * @param  array<string, mixed>|null  $currentMetadata
     * @param  array<string, mixed>  $incomingMetadata
     * @return array<string, mixed>|null
     */
    private function mergeMetadata(?array $currentMetadata, array $incomingMetadata): ?array
    {
        $merged = $currentMetadata ?? [];

        foreach ($incomingMetadata as $key => $value) {
            if (! array_key_exists($key, $merged) || $merged[$key] === null || $merged[$key] === '') {
                $merged[$key] = $value;
            }
        }

        return $merged !== [] ? $merged : null;
    }

    private function describeSkipReason(string $reason): string
    {
        return match ($reason) {
            'missing_name' => 'sin nombre',
            'ambiguous_match' => 'coincidencia ambigua',
            'exception' => 'error durante la actualizacion',
            default => $reason,
        };
    }

    private function resolveFilePath(): string
    {
        $filePath = $this->argument('file');

        if (is_string($filePath) && $filePath !== '') {
            return $filePath;
        }

        $tenantId = tenant()?->id;

        if (! is_string($tenantId) || $tenantId === '') {
            return storage_path('app/clients.json');
        }

        return storage_path("tenant{$tenantId}/app/clients.json");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonRecords(string $filePath): array
    {
        try {
            $contents = file_get_contents($filePath);

            if ($contents === false) {
                throw new RuntimeException('No se pudo leer el archivo JSON.');
            }

            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El archivo JSON no es valido: '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('El archivo JSON debe contener un arreglo en el nivel superior.');
        }

        foreach ($decoded as $index => $record) {
            if (! is_array($record)) {
                throw new RuntimeException('Cada contacto del archivo JSON debe ser un objeto. Error en indice '.$index.'.');
            }
        }

        /** @var list<array<string, mixed>> $decoded */
        return array_values($decoded);
    }

    private function cleanNullable(string $value): ?string
    {
        $cleaned = $this->cleanUtf8String(mb_trim($value));

        if ($cleaned === '' || mb_strtolower($cleaned) === 'null' || mb_strtoupper($cleaned) === 'N/A') {
            return null;
        }

        return $cleaned;
    }

    private function cleanEmail(string $value): ?string
    {
        $email = $this->cleanNullable($value);

        if ($email === null) {
            return null;
        }

        $email = mb_strtolower($email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function cleanPhoneNumber(string $value): ?string
    {
        $phone = $this->cleanNullable($value);

        if ($phone === null) {
            return null;
        }

        $phone = preg_replace('/[^\d+]/', '', $phone);

        return $phone !== null && $phone !== '' ? $phone : null;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        $value = $this->cleanNullable($value);

        if ($value === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function parseGender(string $value): ?string
    {
        $gender = $this->cleanNullable($value);

        if ($gender === null) {
            return null;
        }

        return match (mb_strtolower($gender)) {
            '1', 'm', 'male', 'masculino' => Gender::Male->value,
            '2', 'f', 'female', 'femenino' => Gender::Female->value,
            '-', '0', 'no especificado' => Gender::NotSpecified->value,
            default => null,
        };
    }

    private function parseInteger(string $value): ?int
    {
        $cleaned = $this->cleanNullable($value);

        return $cleaned !== null && is_numeric($cleaned) ? (int) $cleaned : null;
    }

    private function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(mb_trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized !== null && $normalized !== '' ? $normalized : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized !== null && $normalized !== '' ? $normalized : null;
    }

    private function normalizeIdentification(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized !== null && $normalized !== '' ? $normalized : null;
    }

    private function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value instanceof Gender) {
            return $value->value === Gender::NotSpecified->value;
        }

        if (is_string($value)) {
            return mb_trim($value) === '';
        }

        return false;
    }

    /**
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

    private function cleanUtf8String(string $value): string
    {
        $value = str_replace(["\0", "\x0B"], '', $value);

        if (! mb_check_encoding($value, 'UTF-8')) {
            $encoding = mb_detect_encoding($value, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

            if ($encoding && $encoding !== 'UTF-8') {
                $value = mb_convert_encoding($value, 'UTF-8', $encoding);
            } else {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
        }

        $value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        return mb_trim($value);
    }
}
