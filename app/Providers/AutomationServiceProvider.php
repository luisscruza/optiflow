<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Automation\AutomationEngine;
use App\Services\Automation\NodeRunners\ConditionNodeRunner;
use App\Services\Automation\NodeRunners\HttpWebhookNodeRunner;
use App\Services\Automation\NodeRunners\NodeRunnerRegistry;
use App\Services\Automation\NodeRunners\TelegramMessageNodeRunner;
use App\Services\Automation\NodeRunners\WhatsappMessageNodeRunner;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AutomationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NodeRunnerRegistry::class, function () {
            $registry = new NodeRunnerRegistry();

            $registry->register(new HttpWebhookNodeRunner());
            $registry->register(new TelegramMessageNodeRunner());
            $registry->register(new WhatsappMessageNodeRunner());
            $registry->register(new ConditionNodeRunner());

            return $registry;
        });

        $this->app->singleton(AutomationEngine::class);
    }

    public function boot(AutomationEngine $engine): void
    {
        Event::listen('workflow.job.stage_changed', function (array $payload) use ($engine): void {
            $engine->handleWorkflowJobStageChanged($payload);
        });

        Event::listen('invoice.created', function (array $payload) use ($engine): void {
            $engine->handleInvoiceCreated($payload);
        });

        Event::listen('invoice.updated', function (array $payload) use ($engine): void {
            $engine->handleInvoiceUpdated($payload);
        });
    }
}
