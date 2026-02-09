<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContactType;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\ProductStock;
use App\Models\Quotation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Concerns\HasATenantsOption;
use Stancl\Tenancy\Concerns\TenantAwareCommand;

final class MergeDuplicateContacts extends Command
{
    use HasATenantsOption, TenantAwareCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contacts:merge-duplicates {--execute : Apply the merge changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge duplicate contacts by name, email, or phone';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $contacts = Contact::query()
            ->where('contact_type', ContactType::Customer)
            ->get([
                'id',
                'name',
                'email',
                'phone_primary',
                'phone_secondary',
                'mobile',
                'fax',
                'contact_type',
                'identification_type',
                'identification_number',
                'status',
                'observations',
                'credit_limit',
                'metadata',
                'created_at',
                'updated_at',
            ]);

        if ($contacts->isEmpty()) {
            $this->info('No hay contactos para procesar.');

            return self::SUCCESS;
        }

        $groups = $this->buildDuplicateGroups($contacts);
        if ($groups === []) {
            $this->info('No se encontraron duplicados.');

            return self::SUCCESS;
        }

        $this->info('Grupos de duplicados encontrados: '.count($groups));

        $mergedCount = 0;
        $skippedCount = 0;

        foreach ($groups as $group) {
            $result = $this->mergeGroup($group, $execute);
            if ($result) {
                $mergedCount++;
            } else {
                $skippedCount++;
            }
        }

        if (! $execute) {
            $this->warn('Modo reporte: no se aplicaron cambios. Usa --execute para ejecutar la fusion.');
        }

        $this->info("Grupos fusionados: {$mergedCount}");
        $this->info("Grupos omitidos: {$skippedCount}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, Collection<int, Contact>>
     */
    private function buildDuplicateGroups(Collection $contacts): array
    {
        $groups = [];

        return $this->resolveWorkspaceDuplicates($contacts);
    }

    /**
     * @return array<int, Collection<int, Contact>>
     */
    private function resolveWorkspaceDuplicates(Collection $contacts): array
    {
        $parent = [];
        $keyOwner = [];

        foreach ($contacts as $contact) {
            $parent[$contact->id] = $contact->id;
        }

        foreach ($contacts as $contact) {
            foreach ($this->contactKeys($contact) as $key) {
                if (! isset($keyOwner[$key])) {
                    $keyOwner[$key] = $contact->id;

                    continue;
                }

                $this->union($parent, $contact->id, $keyOwner[$key]);
            }
        }

        $grouped = [];
        foreach ($contacts as $contact) {
            $root = $this->find($parent, $contact->id);
            $grouped[$root][] = $contact;
        }

        return array_values(array_filter(
            array_map(fn (array $group): Collection => collect($group), $grouped),
            fn (Collection $group): bool => $group->count() > 1
        ));
    }

    /**
     * @return array<int, string>
     */
    private function contactKeys(Contact $contact): array
    {
        $keys = [];

        $nameKey = $this->normalizeName($contact->name);
        if ($nameKey !== null) {
            $keys[] = 'name:'.$nameKey;
        }

        $emailKey = $this->normalizeEmail($contact->email);
        if ($emailKey !== null) {
            $keys[] = 'email:'.$emailKey;
        }

        foreach ([$contact->phone_primary, $contact->phone_secondary, $contact->mobile, $contact->fax] as $phone) {
            $phoneKey = $this->normalizePhone($phone);
            if ($phoneKey !== null) {
                $keys[] = 'phone:'.$phoneKey;
            }
        }

        return array_values(array_unique($keys));
    }

    private function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(mb_trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeEmail(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(mb_trim($value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        return mb_strlen($normalized) >= 5 ? $normalized : null;
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function find(array &$parent, int $id): int
    {
        if ($parent[$id] !== $id) {
            $parent[$id] = $this->find($parent, $parent[$id]);
        }

        return $parent[$id];
    }

    /**
     * @param  array<int, int>  $parent
     */
    private function union(array &$parent, int $a, int $b): void
    {
        $rootA = $this->find($parent, $a);
        $rootB = $this->find($parent, $b);

        if ($rootA === $rootB) {
            return;
        }

        $parent[$rootB] = $rootA;
    }

    private function mergeGroup(Collection $group, bool $execute): bool
    {
        /** @var Contact $survivor */
        $survivor = $group->sortBy('id')->first();
        $duplicates = $group->where('id', '!=', $survivor->id);

        if ($duplicates->isEmpty()) {
            return false;
        }

        $mergedData = $this->mergeContactData($survivor, $duplicates);

        $this->line('Fusionando contactos: '.implode(', ', $group->pluck('id')->all())." => {$survivor->id}");

        if (! $execute) {
            return true;
        }

        DB::transaction(function () use ($survivor, $duplicates, $mergedData): void {
            if ($mergedData !== []) {
                $survivor->update($mergedData);
            }

            foreach ($duplicates as $duplicate) {
                $this->reassignReferences($duplicate->id, $survivor->id);
                $duplicate->delete();
            }
        });

        return true;
    }

    /**
     * @param  Collection<int, Contact>  $duplicates
     * @return array<string, mixed>
     */
    private function mergeContactData(Contact $survivor, Collection $duplicates): array
    {
        $updates = [];

        $updates['email'] = $this->pickFirstFilled($survivor->email, $duplicates, 'email');
        $updates['phone_primary'] = $this->pickFirstFilled($survivor->phone_primary, $duplicates, 'phone_primary');
        $updates['phone_secondary'] = $this->pickFirstFilled($survivor->phone_secondary, $duplicates, 'phone_secondary');
        $updates['mobile'] = $this->pickFirstFilled($survivor->mobile, $duplicates, 'mobile');
        $updates['fax'] = $this->pickFirstFilled($survivor->fax, $duplicates, 'fax');
        $updates['identification_type'] = $this->pickFirstFilled($survivor->identification_type, $duplicates, 'identification_type');
        $updates['identification_number'] = $this->pickFirstFilled($survivor->identification_number, $duplicates, 'identification_number');
        $updates['status'] = $this->pickFirstFilled($survivor->status, $duplicates, 'status');
        $updates['observations'] = $this->pickFirstFilled($survivor->observations, $duplicates, 'observations');

        $metadata = $this->mergeMetadata($survivor->metadata, $duplicates);
        if ($metadata !== null) {
            $updates['metadata'] = $metadata;
        }

        return array_filter(
            $updates,
            fn ($value, string $key): bool => $value !== $survivor->{$key},
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @param  Collection<int, Contact>  $duplicates
     */
    private function pickFirstFilled(?string $current, Collection $duplicates, string $field): ?string
    {
        if ($this->isFilled($current)) {
            return $current;
        }

        foreach ($duplicates as $duplicate) {
            $value = $duplicate->{$field};
            if ($this->isFilled($value)) {
                return $value;
            }
        }

        return $current;
    }

    /**
     * @param  Collection<int, Contact>  $duplicates
     * @return array<string, mixed>|null
     */
    private function mergeMetadata(?array $metadata, Collection $duplicates): ?array
    {
        $merged = $metadata ?? [];

        foreach ($duplicates as $duplicate) {
            if (! is_array($duplicate->metadata)) {
                continue;
            }

            $merged = array_replace($duplicate->metadata, $merged);
        }

        return $merged !== [] ? $merged : null;
    }

    private function isFilled(?string $value): bool
    {
        return $value !== null && mb_trim($value) !== '';
    }

    private function reassignReferences(int $fromId, int $toId): void
    {
        Invoice::query()->where('contact_id', $fromId)->update(['contact_id' => $toId]);
        Quotation::query()->where('contact_id', $fromId)->update(['contact_id' => $toId]);
        Payment::query()->where('contact_id', $fromId)->update(['contact_id' => $toId]);
        Address::query()->where('contact_id', $fromId)->update(['contact_id' => $toId]);
        Prescription::query()->where('patient_id', $fromId)->update(['patient_id' => $toId]);
        Prescription::query()->where('optometrist_id', $fromId)->update(['optometrist_id' => $toId]);
        ProductStock::query()->where('supplier_id', $fromId)->update(['supplier_id' => $toId]);
    }
}
