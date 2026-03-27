<?php

declare(strict_types=1);

namespace App\Enums;

enum ShareTemplateEntity: string
{
    case Invoice = 'invoice';
    case Quotation = 'quotation';
    case Prescription = 'prescription';
    case Payment = 'payment';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Invoice->value => self::Invoice->label(),
            self::Quotation->value => self::Quotation->label(),
            self::Prescription->value => self::Prescription->label(),
            self::Payment->value => self::Payment->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Facturas',
            self::Quotation => 'Cotizaciones',
            self::Prescription => 'Recetas',
            self::Payment => 'Pagos',
        };
    }
}
