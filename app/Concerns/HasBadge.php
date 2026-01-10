<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasBadge
{
    /**
     * Get the status attribute.
     *
     * @return array<string, mixed>
     */
    protected function statusConfig(): Attribute
    {
        return Attribute::make(get: fn (): array => [
            'value' => $this->status,
            'label' => $this->status->label(),
            'variant' => $this->status->badgeVariant(),
            'className' => $this->status->badgeClassName(),
        ]);
    }
}
