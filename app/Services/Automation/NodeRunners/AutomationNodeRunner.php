<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use App\Services\Automation\Support\AutomationContext;
use App\Services\Automation\Support\NodeResult;

interface AutomationNodeRunner
{
    public function type(): string;

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $input
     */
    public function run(AutomationContext $context, array $config, array $input): NodeResult;
}
