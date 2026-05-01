<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invoice;

final class EasyFactuPayloadTransformer
{
    /**
     * Transform an OpticanNet invoice into an EasyFactu create-invoice payload.
     *
     * @return array<string, mixed>
     */
    public static function toCreatePayload(Invoice $invoice, string $ecfType): array
    {
        $invoice->loadMissing(['contact', 'items.taxes', 'items.product']);

        $payload = [
            'ecf_type' => $ecfType,
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'payment_method' => self::mapPaymentMethod($invoice),
            'currency' => 'DOP',
            'items' => self::transformItems($invoice),
        ];

        // Buyer information from contact
        if ($invoice->contact) {
            if ($invoice->contact->identification_number) {
                $payload['buyer_rnc'] = preg_replace('/\D/', '', $invoice->contact->identification_number);
            }

            if ($invoice->contact->name) {
                $payload['buyer_name'] = $invoice->contact->name;
            }

            if ($invoice->contact->email) {
                $payload['buyer_email'] = $invoice->contact->email;
            }
        }

        if ($invoice->notes) {
            $payload['notes'] = $invoice->notes;
        }

        return $payload;
    }

    /**
     * Transform an OpticanNet invoice into an EasyFactu update-draft payload.
     *
     * @return array<string, mixed>
     */
    public static function toUpdatePayload(Invoice $invoice): array
    {
        $invoice->loadMissing(['contact', 'items.taxes', 'items.product']);

        $payload = [
            'issue_date' => $invoice->issue_date->format('Y-m-d'),
            'payment_method' => self::mapPaymentMethod($invoice),
            'currency' => 'DOP',
            'items' => self::transformItems($invoice),
        ];

        if ($invoice->contact) {
            if ($invoice->contact->identification_number) {
                $payload['buyer_rnc'] = preg_replace('/\D/', '', $invoice->contact->identification_number);
            }

            if ($invoice->contact->name) {
                $payload['buyer_name'] = $invoice->contact->name;
            }

            if ($invoice->contact->email) {
                $payload['buyer_email'] = $invoice->contact->email;
            }
        }

        if ($invoice->notes) {
            $payload['notes'] = $invoice->notes;
        }

        return $payload;
    }

    /**
     * Transform invoice items to the EasyFactu format.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function transformItems(Invoice $invoice): array
    {
        return $invoice->items->map(function ($item): array {
            // Use the primary tax rate from the multi-tax relationship
            $taxRate = 0;

            if ($item->taxes->isNotEmpty()) {
                // Sum all tax rates (e.g., ITBIS 18%)
                $taxRate = $item->taxes->sum('pivot.rate');
            } elseif ($item->tax_rate > 0) {
                // Fallback to the legacy tax_rate column
                $taxRate = (float) $item->tax_rate;
            }

            return [
                'description' => $item->description ?? $item->product?->name ?? 'Artículo',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => $taxRate,
                'discount_rate' => (float) ($item->discount_rate ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Map OpticanNet payment method to EasyFactu's expected format.
     *
     * EasyFactu accepts payment_method as a free string, so we pass through
     * a normalized value. The DGII payload builder on EasyFactu's side
     * maps these to the official DGII indicator codes.
     */
    private static function mapPaymentMethod(Invoice $invoice): string
    {
        // Default to cash if no payment method is associated yet.
        // The payment_term field gives us a hint but the actual payment
        // method is on the Payment model, not the Invoice.
        // For e-CF creation, we default to 'cash' and this can be updated
        // before emission if needed.
        return 'cash';
    }
}
