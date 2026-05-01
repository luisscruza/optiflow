<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Badgeable;

enum ExpenseStatus: string implements Badgeable
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Paid => 'green',
            self::Cancelled => 'red',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'secondary',
            self::Paid => 'default',
            self::Cancelled => 'destructive',
        };
    }

    public function badgeClassName(): ?string
    {
        return match ($this) {
            self::Pending => 'bg-yellow-100 text-yellow-800',
            self::Paid => 'bg-green-100 text-green-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }

    /**
     * @return array{value: string, label: string, variant: string, className: string|null}
     */
    public function toBadge(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'variant' => $this->badgeVariant(),
            'className' => $this->badgeClassName(),
        ];
    }
}
