<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WorkflowFieldType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkflowField>
 */
final class WorkflowFieldFactory extends Factory
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
            'name' => fake()->words(2, true),
            'key' => fake()->unique()->slug(),
            'type' => fake()->randomElement(WorkflowFieldType::cases()),
            'mastertable_id' => null,
            'is_required' => false,
            'placeholder' => fake()->optional()->sentence(),
            'default_value' => fake()->optional()->word(),
            'position' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
