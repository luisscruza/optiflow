<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\SetWorkspaceContext;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\WorkflowJob;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Middleware\SubstituteBindings;
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
    }

    private function enforceMorphMaps(): void
    {
        Relation::enforceMorphMap([
            'invoice' => Invoice::class,
            'workflowjob' => WorkflowJob::class,
            'comment' => Comment::class,
            'contact' => Contact::class,
            'user' => User::class,
            'invoice_item' => InvoiceItem::class,
            'payment' => \App\Models\Payment::class,
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
}
