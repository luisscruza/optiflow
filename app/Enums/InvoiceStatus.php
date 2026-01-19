<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case PendingPayment = 'pending_payment';
    case PartiallyPaid = 'partially_paid';
    case Deleted = 'deleted';

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
            self::Draft->value => self::Draft->label(),
            self::Deleted->value => self::Deleted->label(),
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
            self::Draft => 'Borrador',
            self::Deleted => 'Anulada',
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
            self::Draft => 'outline',
            self::Deleted => 'destructive',
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
            self::Draft => 'bg-gray-100 text-gray-800',
            self::Deleted => 'bg-red-100 text-red-800',
        };
    }

    public function toBadge(): array
    {
        return [
            'label' => $this->label(),
            'variant' => $this->badgeVariant(),
            'className' => $this->badgeClassName(),
        ];
    }
}
