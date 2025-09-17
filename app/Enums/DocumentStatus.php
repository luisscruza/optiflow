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
        ];
    }

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Paid => 'Paid',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }
}
