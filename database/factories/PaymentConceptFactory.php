<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentConcept>
 */
final class PaymentConceptFactory extends Factory
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
            'chart_account_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the concept has an associated chart account.
     */
    public function withChartAccount(?ChartAccount $chartAccount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'chart_account_id' => $chartAccount?->id ?? ChartAccount::factory()->income(),
        ]);
    }

    /**
     * Indicate that the concept is a system concept.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_system' => true,
        ]);
    }

    /**
     * Indicate that the concept is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
