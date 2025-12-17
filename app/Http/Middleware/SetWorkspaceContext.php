<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\BusinessPermission;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\Permission;

final class SetWorkspaceContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (in_array($request->getHost(), config('tenancy.central_domains'))) {
            return $next($request);
        }

        $user = $request->user();

        if ($user) {
            if (! $user->getCurrentWorkspaceSafely() && $user->workspaces->isNotEmpty()) {
                $user->update([
                    'current_workspace_id' => $user->workspaces->first()->id,
                ]);
                $user->refresh();
            }

            $workspace = $user->refresh()->currentWorkspace;

            Context::add('workspace', $workspace);

            app(PermissionRegistrar::class)->setPermissionsTeamId($workspace?->id);

            if (in_array($user->business_role, [UserRole::Owner, UserRole::Admin])) {
                $allPermissions = array_merge(
                    Permission::all(),
                    array_map(fn(BusinessPermission $p) => $p->value, BusinessPermission::allPermissions())
                );

                Inertia::share([
                    'workspace' => [
                        'current' => $workspace,
                        'available' => $user->workspaces,
                    ],
                    'userPermissions' => $allPermissions,
                ]);
            } else {
                $workspacePermissions = $workspace
                    ? $user->getAllPermissions()->pluck('name')->toArray()
                    : [];

                Inertia::share([
                    'workspace' => [
                        'current' => $workspace,
                        'available' => $user->workspaces,
                    ],
                    'userPermissions' => $workspacePermissions,
                ]);
            }
        }

        return $next($request);
    }
}
