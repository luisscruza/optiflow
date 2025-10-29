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
     * Get all contact type values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * Get the display label for the contact type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Supplier => 'Proveedor',
            self::Optometrist => 'Optómetra',
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
