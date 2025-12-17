<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Http\Middleware\SetWorkspaceContext;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Gate;
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
        $this->enforceMorphMaps();
        $this->ensureContextPriority();
        $this->allowSuperAdmin();
    }

    private function enforceMorphMaps(): void
    {
        Relation::enforceMorphMap([
            'invoice' => \App\Models\Invoice::class,
            'comment' => \App\Models\Comment::class,
            'contact' => \App\Models\Contact::class,
            'user' => \App\Models\User::class,
        ]);
    }

    private function ensureContextPriority(): void
    {
        /** @var Kernel $kernel */
        $kernel = app()->make(Kernel::class);

        $kernel->addToMiddlewarePriorityBefore(
            SetWorkspaceContext::class,
            SubstituteBindings::class,
        );
    }

    private function allowSuperAdmin(): void
    {
        Gate::before(function ($user) {
            return in_array($user->business_role, [UserRole::Owner, UserRole::Admin]) ? true : null;
        });
    }
}
