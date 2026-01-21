<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationTrigger>
 */
final class AutomationTriggerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'automation_id' => AutomationFactory::new(),
            'workspace_id' => Workspace::factory(),
            'event_key' => fake()->slug(),
            'workflow_id' => null,
            'workflow_stage_id' => null,
            'is_active' => true,
        ];
    }
}
