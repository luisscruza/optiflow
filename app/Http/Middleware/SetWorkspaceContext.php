<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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
            if (! $user->currentWorkspace && $user->workspaces->isNotEmpty()) {
                $user->update([
                    'current_workspace_id' => $user->workspaces->first()->id,
                ]);
                $user->refresh();
            }

            $workspace = $user->refresh()->currentWorkspace;

            Context::add('workspace', $workspace);

            Inertia::share([
                'workspace' => [
                    'current' => $workspace,
                    'available' => $user->workspaces,
                ],
            ]);
        }

        return $next($request);
    }
}
