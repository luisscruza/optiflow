<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Completed = 'completed';
    case Voided = 'voided';

    /**
     * Get all payment statuses as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Completed->value => self::Completed->label(),
            self::Voided->value => self::Voided->label(),
        ];
    }

    /**
     * Get the display label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Completado',
            self::Voided => 'Anulado',
        };
    }

    /**
     * Get the color for display purposes.
     */
    public function color(): string
    {
        return match ($this) {
            self::Completed => 'green',
            self::Voided => 'red',
        };
    }

    /**
     * Get the icon for display purposes.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Completed => 'check-circle',
            self::Voided => 'x-circle',
        };
    }
}
