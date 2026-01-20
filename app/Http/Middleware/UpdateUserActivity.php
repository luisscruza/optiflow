<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! tenant()) {
            return $next($request);
        }

        try {
            $last = $user->getAttribute('last_activity_at');
        } catch (\Illuminate\Database\Eloquent\MissingAttributeException) {
            $user->forceFill(['last_activity_at' => now()])->saveQuietly();

            return $next($request);
        }

        if (is_string($last)) {
            $last = Carbon::parse($last);
        }

        if (! $last instanceof CarbonInterface || $last->lt(now()->subSeconds(120))) {
            $user->forceFill(['last_activity_at' => now()])->saveQuietly();
        }

        return $next($request);
    }
}
