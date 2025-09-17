<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case Invoice = 'invoice';
    case Quotation = 'quotation';

    /**
     * Get all document types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Invoice->value => self::Invoice->label(),
            self::Quotation->value => self::Quotation->label(),
        ];
    }

    /**
     * Get the display label for the document type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Factura',
            self::Quotation => 'Cotización',
        };
    }
}
