<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use RuntimeException;

final class NodeRunnerRegistry
{
    /**
     * @var array<string, AutomationNodeRunner>
     */
    private array $runners = [];

    public function register(AutomationNodeRunner $runner): void
    {
        $this->runners[$runner->type()] = $runner;
    }

    public function get(string $type): AutomationNodeRunner
    {
        $runner = $this->runners[$type] ?? null;

        if (! $runner instanceof AutomationNodeRunner) {
            throw new RuntimeException("No node runner registered for type [$type].");
        }

        return $runner;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->runners);
    }
}
