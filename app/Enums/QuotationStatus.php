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
            self::Converted => 'Facturada',
            self::NonConverted => 'Sin facturar',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Get the badge variant for the document type.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'outline',
            self::Converted => 'default',
            self::NonConverted => 'secondary',
            self::Cancelled => 'destructive',
        };
    }

    /**
     * Get the badge class name for the document type.
     */
    public function badgeClassName(): string
    {
        return match ($this) {
            self::Draft => 'bg-yellow-100 text-yellow-800',
            self::Converted => 'bg-green-100 text-green-800',
            self::NonConverted => 'bg-yellow-200 text-gray-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }
}
