<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class EasyFactuInvoiceMetadata
{
    /**
     * @param  array<string, mixed>  $invoice
     */
    public static function extractSignedAt(array $invoice): ?string
    {
        $signedAt = $invoice['signed_at'] ?? null;

        if (is_string($signedAt) && $signedAt !== '') {
            return $signedAt;
        }

        $qrCodeUrl = $invoice['qr_code_url'] ?? null;

        if (! is_string($qrCodeUrl) || $qrCodeUrl === '') {
            return null;
        }

        $query = parse_url($qrCodeUrl, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $queryParams);

        $rawSignedAt = $queryParams['fechafirma'] ?? null;

        if (! is_string($rawSignedAt) || $rawSignedAt === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('d-m-Y H:i:s', $rawSignedAt, config('app.timezone'))?->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }
}
