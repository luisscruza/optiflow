<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HasWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->currentWorkspace) {
            return $next($request);
        }

        return redirect()->route('workspaces.index')
            ->with('error', 'Please select a workspace to continue.');
    }
}
