<?php

declare(strict_types=1);

namespace App\Enums;

enum BankAccountType: string
{
    case Bank = 'bank';
    case CreditCard = 'credit_card';
    case Cash = 'cash';
    case Other = 'other';

    /**
     * Get all contact types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Bank->value => self::Bank->label(),
            self::CreditCard->value => self::CreditCard->label(),
            self::Cash->value => self::Cash->label(),
            self::Other->value => self::Other->label(),
        ];
    }

    /**
     * Get the display label for the contact type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Banco',
            self::CreditCard => 'Tarjeta de Crédito',
            self::Cash => 'Efectivo',
            self::Other => 'Otro',
        };
    }

    /**
     * Get the display label for the contact type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Bank => 'Cuenta bancaria nacional tarjeta de débito',
            self::CreditCard => 'Tarjeta de Crédito',
            self::Cash => 'Caja general o caja menor',
            self::Other => 'Otro',
        };
    }
}
