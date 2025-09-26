<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case PendingPayment = 'pending_payment';
    case PartiallyPaid = 'partially_paid';

    /**
     * Get all contact types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Paid->value => self::Paid->label(),
            self::Cancelled->value => self::Cancelled->label(),
            self::PendingPayment->value => self::PendingPayment->label(),
            self::PartiallyPaid->value => self::PartiallyPaid->label(),
        ];
    }

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Pagada',
            self::Cancelled => 'Cancelada',
            self::PendingPayment => 'Pendiente de pago',
            self::PartiallyPaid => 'Parcialmente pagada',
        };
    }

    /**
     * Get the badge variant for the document type.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Paid => 'default',
            self::Cancelled => 'destructive',
            self::PendingPayment => 'outline',
            self::PartiallyPaid => 'secondary',
        };
    }

    /**
     * Get the badge class name for the document type.
     */
    public function badgeClassName(): string
    {
        return match ($this) {
            self::Paid => 'bg-green-100 text-green-800',
            self::Cancelled => 'bg-red-100 text-red-800',
            self::PendingPayment => 'bg-yellow-100 text-yellow-800',
            self::PartiallyPaid => 'bg-blue-100 text-blue-800',
        };
    }
}
