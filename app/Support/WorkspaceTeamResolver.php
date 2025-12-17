<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Context;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

final class WorkspaceTeamResolver implements PermissionsTeamResolver
{
    private int|string|null $teamId = null;

    /**
     * Set the team id for teams/groups support, this id is used when querying permissions/roles
     *
     * @param  int|string|\Illuminate\Database\Eloquent\Model|null  $id
     */
    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof \Illuminate\Database\Eloquent\Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        return Context::get('workspace')->id ?? null;

    }
}
