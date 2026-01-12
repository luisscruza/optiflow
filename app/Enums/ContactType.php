<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Badgeable;

enum ContactType: string implements Badgeable
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
            self::Optometrist => 'Evaluador',
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

    public function color(): string
    {
        return match ($this) {
            self::Customer => 'blue',
            self::Supplier => 'purple',
            self::Optometrist => 'gray',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Customer => 'primary',
            self::Supplier => 'secondary',
            self::Optometrist => 'default',
        };
    }

    public function badgeClassName(): string
    {
        return match ($this) {
            self::Customer => 'bg-blue-100 text-blue-800',
            self::Supplier => 'bg-purple-100 text-purple-800',
            self::Optometrist => 'bg-gray-100 text-gray-800',
        };
    }
}
