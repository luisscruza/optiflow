<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Spatie\Permission\Models\Permission as SpatiePermission;

final class Permission extends SpatiePermission
{
    /**
     * Get a human-readable label for this permission.
     */
    public function getLabel(): string
    {
        return PermissionEnum::tryFrom($this->name)?->label()
            ?? ucfirst(str_replace(['.', '_'], ' ', $this->name));
    }

    /**
     * Get the group for this permission.
     */
    public function getGroup(): string
    {
        return PermissionEnum::tryFrom($this->name)?->group()
            ?? ucfirst(explode('.', $this->name)[0]);
    }
}
