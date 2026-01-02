<?php

declare(strict_types=1);

namespace App\Filters;

use Closure;

abstract class QueryFilter
{
    abstract protected function shouldApply(): bool;

    abstract protected function apply($query);

    final public function handle($query, Closure $next)
    {
        if (! $this->shouldApply()) {
            return $next($query);
        }

        return $next($this->apply($query));
    }
}
