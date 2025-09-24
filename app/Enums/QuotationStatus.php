<?php

declare(strict_types=1);

namespace App\Enums;

enum QuotationStatus: string
{
    case Converted = 'converted';
    case Draft = 'draft';
    case NonConverted = 'non_converted';
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
            self::Converted->value => self::Converted->label(),
            self::NonConverted->value => self::NonConverted->label(),
            self::Cancelled->value => self::Cancelled->label(),
        ];
    }

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Converted => 'Convertido',
            self::NonConverted => 'No Convertido',
            self::Cancelled => 'Cancelado',
        };
    }
}
