<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

final class Impersonator extends Facade
{
    /**
     * Create a new class instance.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'impersonator';
    }
}
