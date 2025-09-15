<?php

declare(strict_types=1);

namespace App\Enums;

enum IdentificationType: string
{
    case Cedula = 'cedula';
    case RNC = 'rnc';
    case Pasaporte = 'pasaporte';

    /**
     * Get all identification types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Cedula->value => self::Cedula->label(),
            self::RNC->value => self::RNC->label(),
            self::Pasaporte->value => self::Pasaporte->label(),
        ];
    }

    /**
     * Get the display label for the identification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cedula => 'Cédula',
            self::RNC => 'RNC',
            self::Pasaporte => 'Pasaporte',
        };
    }
}
