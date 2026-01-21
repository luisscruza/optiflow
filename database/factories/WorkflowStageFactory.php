<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowStage>
 */
final class WorkflowStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => WorkflowFactory::new(),
            'name' => fake()->word(),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'position' => fake()->numberBetween(1, 10),
            'is_active' => true,
            'is_initial' => false,
            'is_final' => false,
        ];
    }
}
