<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DocumentSubtype;
use App\Models\Invoice;

final class NCFValidator
{
    public static function validate(string $ncf, DocumentSubtype $documentSubtype, array $data): bool
    {
        if (Invoice::where('document_number', $ncf)->exists()) {
            return false;
        }

        $prefix = mb_substr($ncf, 0, mb_strlen($documentSubtype->prefix));
        $number = (int) ltrim(mb_substr($ncf, mb_strlen($documentSubtype->prefix)), '0');

        if ($prefix !== $documentSubtype->prefix) {
            return false;
        }

        if (! empty($documentSubtype->valid_until_date) && isset($data['issue_date'])) {
            $issueDate = \Carbon\Carbon::parse($data['issue_date']);
            if ($issueDate->gt($documentSubtype->valid_until_date)) {
                return false;
            }
        }

        if ($documentSubtype->end_number !== null && $number > $documentSubtype->end_number) {
            return false;
        }

        if ($documentSubtype->start_number !== null && $number < $documentSubtype->start_number) {
            return false;
        }

        if ($number < $documentSubtype->next_number) {
            return false;
        }

        return true;
    }
}
