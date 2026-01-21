<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationNodeRun>
 */
final class AutomationNodeRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'automation_run_id' => AutomationRunFactory::new(),
            'node_id' => fake()->uuid(),
            'node_type' => fake()->word(),
            'status' => 'running',
            'attempts' => 0,
            'input' => null,
            'output' => null,
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
