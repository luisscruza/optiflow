<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationRun>
 */
final class AutomationRunFactory extends Factory
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
            'automation_version_id' => AutomationVersionFactory::new(),
            'workspace_id' => Workspace::factory(),
            'trigger_event_key' => fake()->slug(),
            'subject_type' => 'contact',
            'subject_id' => fake()->uuid(),
            'status' => 'running',
            'pending_nodes' => 0,
            'started_at' => fake()->optional()->dateTime(),
            'finished_at' => null,
            'error' => null,
        ];
    }
}
