<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Optometrist = 'optometrist';

    /**
     * Get all contact types as an array for form options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Customer->value => self::Customer->label(),
            self::Supplier->value => self::Supplier->label(),
            self::Optometrist->value => self::Optometrist->label(),
        ];
    }

    /**
     * Get the display label for the contact type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Supplier => 'Proveedor',
            self::Optometrist => 'Optometrista',
        };
    }

    /**
     * Check if the contact type is a customer.
     */
    public function isCustomer(): bool
    {
        return $this === self::Customer;
    }

    /**
     * Check if the contact type is a supplier.
     */
    public function isSupplier(): bool
    {
        return $this === self::Supplier;
    }
}
