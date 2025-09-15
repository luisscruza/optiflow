<?php

declare(strict_types=1);

namespace App;

enum ContactType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both = 'both';

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
            self::Both->value => self::Both->label(),
        ];
    }

    /**
     * Get customer types.
     *
     * @return array<ContactType>
     */
    public static function customerTypes(): array
    {
        return [self::Customer, self::Both];
    }

    /**
     * Get supplier types.
     *
     * @return array<ContactType>
     */
    public static function supplierTypes(): array
    {
        return [self::Supplier, self::Both];
    }

    /**
     * Get the values for customer types.
     *
     * @return array<string>
     */
    public static function customerValues(): array
    {
        return array_map(fn (ContactType $type) => $type->value, self::customerTypes());
    }

    /**
     * Get the values for supplier types.
     *
     * @return array<string>
     */
    public static function supplierValues(): array
    {
        return array_map(fn (ContactType $type) => $type->value, self::supplierTypes());
    }

    /**
     * Get the display label for the contact type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Supplier => 'Proveedor',
            self::Both => 'Cliente y Proveedor',
        };
    }

    /**
     * Check if the contact type is a customer.
     */
    public function isCustomer(): bool
    {
        return $this === self::Customer || $this === self::Both;
    }

    /**
     * Check if the contact type is a supplier.
     */
    public function isSupplier(): bool
    {
        return $this === self::Supplier || $this === self::Both;
    }
}
