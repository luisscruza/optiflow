<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
final class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ReportType::cases());

        return [
            'type' => $type,
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'group' => $type->group(),
            'config' => null,
            'is_active' => true,
        ];
    }
}
