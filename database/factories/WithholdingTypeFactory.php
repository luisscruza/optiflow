<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WithholdingType>
 */
final class WithholdingTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'percentage' => fake()->randomFloat(2, 1, 30),
            'chart_account_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the withholding type has an associated chart account.
     */
    public function withChartAccount(?ChartAccount $chartAccount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'chart_account_id' => $chartAccount?->id ?? ChartAccount::factory()->liability(),
        ]);
    }

    /**
     * Indicate that the withholding type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific percentage.
     */
    public function withPercentage(float $percentage): static
    {
        return $this->state(fn (array $attributes): array => [
            'percentage' => $percentage,
        ]);
    }
}
