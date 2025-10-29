<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentSubtype;
use App\Models\Invoice;

final readonly class ValidateNFCAction
{
    /**
     * Execute the action.
     */
    public function handle(string $ncf, ?int $invoiceId = null): array
    {

        $prefix = $this->extractPrefix($ncf);

        if (! $prefix) {
            return [
                'valid' => false,
                'message' => 'Formato de NCF inválido',
            ];
        }

        $documentSubtype = DocumentSubtype::findByPrefix($prefix);

        if (! $documentSubtype) {
            return [
                'valid' => false,
                'message' => 'Prefijo de NCF no encontrado',
            ];
        }

        if (! $documentSubtype->isValid()) {
            return [
                'valid' => false,
                'message' => 'La secuencia de NCF está expirada o agotada',
            ];
        }

        $numberPart = mb_substr($ncf, mb_strlen($prefix));
        $ncfNumber = (int) mb_ltrim($numberPart, '0');

        if ($ncfNumber < $documentSubtype->next_number) {
            return [
                'valid' => false,
                'message' => "El número debe ser igual o mayor a {$documentSubtype->next_number} ({$prefix}".mb_str_pad((string) $documentSubtype->next_number, 8, '0', STR_PAD_LEFT).')',
            ];
        }

        $duplicateQuery = Invoice::query()->where('document_number', $ncf);

        if ($invoiceId) {
            $duplicateQuery->where('id', '!=', $invoiceId);
        }

        if ($duplicateQuery->exists()) {
            return [
                'valid' => false,
                'message' => 'Este NCF ya está en uso por otra factura',
            ];
        }

        return [
            'valid' => true,
            'message' => 'NCF válido',
        ];
    }

    /**
     * Extract prefix from NCF (assumes first 3 characters for Dominican NCF format).
     */
    private function extractPrefix(string $ncf): ?string
    {
        // Dominican NCF format: B01, B02, B14, B15, etc. (3 characters)
        if (mb_strlen($ncf) < 3) {
            return null;
        }

        return mb_substr($ncf, 0, 3);
    }
}
