<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

final class BusinessUserController extends Controller
{
    /**
     * Display a listing of all users in the business with their workspace memberships.
     */
    public function index(): Response
    {
        $users = User::query()
            ->with([
                'workspaces' => function ($query) {
                    $query->select('workspaces.id', 'workspaces.name', 'workspaces.owner_id')
                        ->withPivot(['role', 'joined_at']);
                },
            ])
            ->withCount('workspaces')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {

                $roles = DB::table('model_has_roles')
                    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->where('model_id', '=', $user->id)
                    ->get()->toArray();

                $lastActivity = $user->last_activity_at;

                if (is_string($lastActivity)) {
                    $lastActivity = Carbon::parse($lastActivity);
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'last_activity_at' => $lastActivity instanceof CarbonInterface
                        ? $lastActivity->diffForHumans()
                        : 'Sin actividad',
                    'business_role' => $user->business_role->label(),
                    'workspaces_count' => $user->workspaces_count,
                    'workspaces' => $user->workspaces->map(function ($workspace) use ($user, $roles): array {
                        /** @var \Illuminate\Database\Eloquent\Relations\Pivot|null $pivot */
                        $pivot = $workspace->getRelationValue('pivot');
                        $workspaceRoles = array_filter($roles, fn ($role) => $role->workspace_id === $workspace->id);

                        return [
                            'id' => $workspace->id,
                            'name' => $workspace->name,
                            'is_owner' => $workspace->owner_id === $user->id,
                            'pivot_role' => $pivot?->getAttribute('role'),
                            'joined_at' => $pivot?->getAttribute('joined_at'),
                            'roles' => array_values(array_map(fn ($role) => [
                                'id' => $role->id,
                                'name' => $role->name,
                            ], $workspaceRoles)),
                        ];
                    })->toArray(),
                ];
            });

        $workspaces = Workspace::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $rolesByWorkspace = Role::query()
            ->select('id', 'name', 'workspace_id')
            ->orderBy('workspace_id')
            ->orderBy('name')
            ->get()
            ->groupBy('workspace_id');

        return Inertia::render('business/users', [
            'users' => $users,
            'workspaces' => $workspaces,
            'rolesByWorkspace' => $rolesByWorkspace,
        ]);
    }
}
