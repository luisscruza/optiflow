<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
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
        if ($request->user() && $request->user()->currentWorkspace) {
            // Set the current workspace in context
            Context::add('workspace', $request->user()->currentWorkspace);

            // Share workspace data with Inertia
            \Inertia\Inertia::share([
                'workspace' => [
                    'current' => $request->user()->currentWorkspace,
                    'available' => $request->user()->workspaces,
                ],
            ]);
        }

        return $next($request);
    }
}
