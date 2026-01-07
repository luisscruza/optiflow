<?php

declare(strict_types=1);

namespace App\Enums;

enum ChartAccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Income = 'income';
    case Expense = 'expense';

    /**
     * Get all account types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Asset->value => self::Asset->label(),
            self::Liability->value => self::Liability->label(),
            self::Equity->value => self::Equity->label(),
            self::Income->value => self::Income->label(),
            self::Expense->value => self::Expense->label(),
        ];
    }

    /**
     * Get the display label for the account type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Activo',
            self::Liability => 'Pasivo',
            self::Equity => 'Patrimonio',
            self::Income => 'Ingreso',
            self::Expense => 'Gasto',
        };
    }

    /**
     * Get the description for the account type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Asset => 'Bienes y derechos de la empresa (efectivo, cuentas por cobrar, inventario)',
            self::Liability => 'Obligaciones y deudas de la empresa (cuentas por pagar, préstamos)',
            self::Equity => 'Capital y reservas de los propietarios',
            self::Income => 'Ingresos por ventas y servicios',
            self::Expense => 'Gastos operativos y administrativos',
        };
    }

    /**
     * Get the typical account code prefix for this type.
     */
    public function codePrefix(): string
    {
        return match ($this) {
            self::Asset => '1',
            self::Liability => '2',
            self::Equity => '3',
            self::Income => '4',
            self::Expense => '5',
        };
    }
}
