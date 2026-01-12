<?php

declare(strict_types=1);

namespace App\Contracts;

interface Badgeable
{
    public function label(): string;

    public function color(): string;

    public function badgeVariant(): string;

    public function badgeClassName(): ?string;
}
