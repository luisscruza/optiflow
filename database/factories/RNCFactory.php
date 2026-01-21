<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RNC>
 */
final class RNCFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identification' => fake()->numerify('#########'),
            'name' => fake()->company(),
            'comercial_name' => fake()->company(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
