<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AutomationVersion>
 */
final class AutomationVersionFactory extends Factory
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
            'version' => fake()->numberBetween(1, 10),
            'definition' => ['nodes' => []],
            'created_by' => User::factory(),
        ];
    }
}
