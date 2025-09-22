<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case NonConverted = 'non_converted';
    case Converted = 'converted';

    case PendingPayment = 'pending_payment';

    /**
     * Get all contact types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => self::Draft->label(),
            self::Sent->value => self::Sent->label(),
            self::Paid->value => self::Paid->label(),
            self::Overdue->value => self::Overdue->label(),
            self::Cancelled->value => self::Cancelled->label(),
            self::NonConverted->value => self::NonConverted->label(),
            self::Converted->value => self::Converted->label(),
            self::PendingPayment->value => self::PendingPayment->label(),
        ];
    }

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Paid => 'Pagada',
            self::Overdue => 'Vencida',
            self::Cancelled => 'Cancelada',
            self::NonConverted => 'Non Converted',
            self::Converted => 'Converted',
            self::PendingPayment => 'Pending Payment',
        };
    }
}
