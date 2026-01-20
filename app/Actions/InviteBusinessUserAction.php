<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Exceptions\ActionValidationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

final readonly class InviteBusinessUserAction
{
    public function __construct(private AssignUserToWorkspaceAction $assignUserAction) {}

    /**
     * @param  array{email: string, name: string, workspaces: array<int, array{workspace_id: int, role_id?: int|null}>}  $data
     */
    public function handle(array $data, User $currentUser): User
    {
        return DB::transaction(function () use ($data, $currentUser): User {
            $workspaceAssignments = collect($data['workspaces'])
                ->map(fn (array $assignment): array => [
                    'workspace_id' => $assignment['workspace_id'],
                    'role' => UserRole::User,
                ])
                ->toArray();

            $tempPassword = 'temp-'.Str::random(12);

            try {
                $user = $this->assignUserAction->handle(
                    email: $data['email'],
                    name: $data['name'],
                    password: $tempPassword,
                    workspaceAssignments: $workspaceAssignments,
                    businessRole: UserRole::User,
                    assignedBy: $currentUser
                );
            } catch (InvalidArgumentException $exception) {
                throw new ActionValidationException([
                    'email' => $exception->getMessage(),
                ]);
            }

            foreach ($data['workspaces'] as $workspaceData) {
                if (isset($workspaceData['role_id'])) {
                    $role = Role::find($workspaceData['role_id']);
                    $roleWorkspaceId = $role?->getAttribute('workspace_id');

                    if ($roleWorkspaceId !== null && (int) $roleWorkspaceId === (int) $workspaceData['workspace_id']) {
                        setPermissionsTeamId($workspaceData['workspace_id']);
                        $user->assignRole($role);
                    }
                }
            }

            return $user;
        });
    }
}
