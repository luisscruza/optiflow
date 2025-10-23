<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'invoice' => \App\Models\Invoice::class,
            'comment' => \App\Models\Comment::class,
            'contact' => \App\Models\Contact::class,
            'user' => \App\Models\User::class,
        ]);
    }
}
