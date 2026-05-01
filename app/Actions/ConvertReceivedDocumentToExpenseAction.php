<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactType;
use App\Enums\ExpenseStatus;
use App\Enums\IdentificationType;
use App\Exceptions\ReportableActionException;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Arr;

final readonly class ConvertReceivedDocumentToExpenseAction
{
    public function __construct(
        private CreateExpenseAction $createExpense,
    ) {}

    /**
     * @param  array<string, mixed>  $document
     * @return array{expense: Expense, created: bool, supplier_created: bool}
     */
    public function handle(User $user, array $document): array
    {
        $workspaceId = (int) $user->current_workspace_id;
        $receivedDocumentId = $this->nullableString($document['id'] ?? null);

        if ($workspaceId <= 0) {
            throw new ReportableActionException('Debes tener una sucursal activa para convertir el documento en gasto.');
        }

        if ($receivedDocumentId === null) {
            throw new ReportableActionException('El documento recibido no tiene un identificador válido de EasyFactu.');
        }

        $documentNumber = mb_trim((string) ($document['encf'] ?? ''));

        if ($documentNumber === '') {
            throw new ReportableActionException('El documento recibido no tiene un e-NCF válido para convertirlo en gasto.');
        }

        [$supplier, $supplierCreated] = $this->resolveSupplier($document);

        $existingExpense = Expense::query()
            ->withoutWorkspaceScope()
            ->where('workspace_id', $workspaceId)
            ->where('contact_id', $supplier->id)
            ->where('document_number', $documentNumber)
            ->first();

        if ($existingExpense instanceof Expense) {
            if ($existingExpense->easyfactu_received_document_id === null) {
                $existingExpense->update([
                    'easyfactu_received_document_id' => $receivedDocumentId,
                ]);
            }

            return [
                'expense' => $existingExpense->fresh(),
                'created' => false,
                'supplier_created' => false,
            ];
        }

        $taxAmount = round((float) ($document['tax_amount'] ?? 0), 2);
        $subtotalAmount = $this->resolveSubtotal($document, $taxAmount);

        $expense = $this->createExpense->handle($user, [
            'workspace_id' => $workspaceId,
            'contact_id' => $supplier->id,
            'document_number' => $documentNumber,
            'easyfactu_received_document_id' => $receivedDocumentId,
            'issue_date' => (string) ($document['issue_date'] ?? now()->toDateString()),
            'subtotal_amount' => $subtotalAmount,
            'itbis_amount' => $taxAmount,
            'isc_amount' => 0,
            'withheld_itbis_amount' => 0,
            'withheld_isr_amount' => 0,
            'is_informal' => false,
            'status' => ExpenseStatus::Pending->value,
        ]);

        return [
            'expense' => $expense,
            'created' => true,
            'supplier_created' => $supplierCreated,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array{0: Contact, 1: bool}
     */
    private function resolveSupplier(array $document): array
    {
        $supplier = is_array($document['supplier'] ?? null) ? $document['supplier'] : [];

        $supplierId = $this->nullableString($supplier['id'] ?? null);
        $supplierName = $this->nullableString($supplier['name'] ?? null);
        $supplierRnc = $this->nullableString($supplier['rnc'] ?? null);
        $supplierEmail = $this->nullableString($supplier['email'] ?? null);
        $supplierPhone = $this->nullableString($supplier['phone'] ?? null);
        $supplierAddress = $this->nullableString($supplier['address'] ?? null);

        if ($supplierName === null) {
            throw new ReportableActionException('El documento recibido no tiene un suplidor válido para crear el gasto.');
        }

        $contact = $this->findSupplier($supplierId, $supplierRnc);

        if ($contact instanceof Contact) {
            $this->fillSupplierDetails($contact, [
                'name' => $supplierName,
                'email' => $supplierEmail,
                'phone_primary' => $supplierPhone,
                'identification_number' => $supplierRnc,
                'metadata' => $this->metadataForSupplier($supplierId),
            ]);
            $this->fillSupplierAddress($contact, $supplierAddress);

            return [$contact->fresh(), false];
        }

        $contact = Contact::query()->create([
            'name' => $supplierName,
            'contact_type' => ContactType::Supplier,
            'email' => $supplierEmail,
            'phone_primary' => $supplierPhone,
            'identification_type' => $supplierRnc !== null ? IdentificationType::RNC : null,
            'identification_number' => $supplierRnc,
            'status' => 'active',
            'metadata' => $this->metadataForSupplier($supplierId),
            'credit_limit' => 0,
            'observations' => null,
        ]);

        $this->fillSupplierAddress($contact, $supplierAddress);

        return [$contact, true];
    }

    private function findSupplier(?string $supplierId, ?string $supplierRnc): ?Contact
    {
        $normalizedRnc = $supplierRnc !== null ? $this->normalizeIdentification($supplierRnc) : null;

        $contact = Contact::query()
            ->suppliers()
            ->when($supplierRnc !== null || $normalizedRnc !== null || $supplierId !== null, function ($query) use ($supplierId, $supplierRnc, $normalizedRnc): void {
                $query->where(function ($builder) use ($supplierId, $supplierRnc, $normalizedRnc): void {
                    if ($supplierId !== null) {
                        $builder->orWhere('metadata', 'like', '%"easyfactu_supplier_id":"'.$supplierId.'"%');
                    }

                    if ($supplierRnc !== null) {
                        $builder->orWhere('identification_number', $supplierRnc);
                    }

                    if ($normalizedRnc !== null && $normalizedRnc !== $supplierRnc) {
                        $builder->orWhere('identification_number', $normalizedRnc);
                    }
                });
            })
            ->first();

        if ($contact instanceof Contact || $normalizedRnc === null) {
            return $contact;
        }

        return Contact::query()
            ->suppliers()
            ->whereNotNull('identification_number')
            ->get()
            ->first(fn (Contact $supplier): bool => $this->normalizeIdentification($supplier->identification_number) === $normalizedRnc);
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function resolveSubtotal(array $document, float $taxAmount): float
    {
        $subtotal = Arr::get($document, 'subtotal');

        if (is_numeric($subtotal)) {
            return round((float) $subtotal, 2);
        }

        return round(max((float) ($document['total_amount'] ?? 0) - $taxAmount, 0), 2);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function fillSupplierDetails(Contact $contact, array $values): void
    {
        $updates = [];

        foreach (['email', 'phone_primary', 'identification_number'] as $field) {
            if ($this->isEmpty($contact->{$field}) && ! $this->isEmpty($values[$field] ?? null)) {
                $updates[$field] = $values[$field];
            }
        }

        if ($this->isEmpty($contact->identification_type) && ! $this->isEmpty($values['identification_number'] ?? null)) {
            $updates['identification_type'] = IdentificationType::RNC;
        }

        $metadata = is_array($contact->metadata) ? $contact->metadata : [];
        $incomingMetadata = is_array($values['metadata'] ?? null) ? $values['metadata'] : [];
        $mergedMetadata = array_merge($metadata, array_filter($incomingMetadata, fn ($value): bool => $value !== null && $value !== ''));

        if ($mergedMetadata !== $metadata) {
            $updates['metadata'] = $mergedMetadata;
        }

        if ($updates !== []) {
            $contact->update($updates);
        }
    }

    private function fillSupplierAddress(Contact $contact, ?string $address): void
    {
        if ($this->isEmpty($address)) {
            return;
        }

        $primaryAddress = $contact->primaryAddress()->first();

        if ($primaryAddress === null) {
            $contact->addresses()->create([
                'type' => 'billing',
                'description' => $address,
                'country' => 'República Dominicana',
                'is_primary' => true,
            ]);

            return;
        }

        if ($this->isEmpty($primaryAddress->description)) {
            $primaryAddress->update([
                'description' => $address,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function metadataForSupplier(?string $supplierId): array
    {
        return $supplierId !== null ? ['easyfactu_supplier_id' => $supplierId] : [];
    }

    private function normalizeIdentification(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = mb_trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && mb_trim($value) === '');
    }
}
