<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceUserAssignedNotification;
use App\Notifications\WorkspaceUserCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final readonly class AssignUserToWorkspaceAction
{
    /**
     * Assign a user to workspace(s). Create user if they don't exist.
     *
     * @param  array<int, array{workspace_id: int, role: UserRole}>  $workspaceAssignments
     */
    public function handle(
        string $email,
        ?string $name = null,
        ?string $password = null,
        array $workspaceAssignments = [],
        ?UserRole $businessRole = null,
        ?User $assignedBy = null
    ): User {
        return DB::transaction(function () use ($email, $name, $password, $workspaceAssignments, $businessRole, $assignedBy): User {
            $user = User::where('email', $email)->first();
            $isNewUser = false;

            if (! $user) {
                if ($name === null || $name === '' || $name === '0' || ($password === null || $password === '' || $password === '0')) {
                    throw new InvalidArgumentException('Name and password are required for new users');
                }
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ];
                if ($businessRole instanceof UserRole) {
                    $userData['business_role'] = $businessRole;
                }
                $user = User::create($userData);
                $isNewUser = true;
            } elseif ($businessRole && $user->business_role !== $businessRole) {
                $user->business_role = $businessRole;
                $user->save();
            }

            // Assign user to workspaces
            foreach ($workspaceAssignments as $assignment) {
                $workspace = Workspace::findOrFail($assignment['workspace_id']);
                $role = $assignment['role'];

                if (! $workspace->users()->where('user_id', $user->id)->exists()) {
                    $workspace->users()->attach($user->id, [
                        'role' => $role->value,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if (! $user->current_workspace_id) {
                        $user->current_workspace_id = $workspace->id;
                        $user->save();
                    }
                }
            }

            if ($isNewUser && $assignedBy && $workspaceAssignments !== []) {
                $firstWorkspace = Workspace::findOrFail($workspaceAssignments[0]['workspace_id']);
                $user->notify(new WorkspaceUserCreatedNotification(
                    $firstWorkspace,
                    $assignedBy,
                    $password
                ));
            } elseif (! $isNewUser && $assignedBy && $workspaceAssignments !== []) {
                $user->notify(new WorkspaceUserAssignedNotification(
                    collect($workspaceAssignments)->map(fn ($assignment) => Workspace::findOrFail($assignment['workspace_id'])),
                    $assignedBy
                ));
            }

            return $user;
        });
    }
}
