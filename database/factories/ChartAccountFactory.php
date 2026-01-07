<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChartAccountType;
use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChartAccount>
 */
final class ChartAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(ChartAccountType::cases());

        return [
            'code' => $type->codePrefix().'.'.fake()->unique()->numerify('##.##'),
            'name' => fake()->words(3, true),
            'type' => $type,
            'parent_id' => null,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_system' => false,
        ];
    }

    /**
     * Indicate that the account is an asset.
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ChartAccountType::Asset,
            'code' => '1.'.fake()->unique()->numerify('##.##'),
        ]);
    }

    /**
     * Indicate that the account is a liability.
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ChartAccountType::Liability,
            'code' => '2.'.fake()->unique()->numerify('##.##'),
        ]);
    }

    /**
     * Indicate that the account is equity.
     */
    public function equity(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ChartAccountType::Equity,
            'code' => '3.'.fake()->unique()->numerify('##.##'),
        ]);
    }

    /**
     * Indicate that the account is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ChartAccountType::Income,
            'code' => '4.'.fake()->unique()->numerify('##.##'),
        ]);
    }

    /**
     * Indicate that the account is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ChartAccountType::Expense,
            'code' => '5.'.fake()->unique()->numerify('##.##'),
        ]);
    }

    /**
     * Indicate that the account is a system account.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_system' => true,
        ]);
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a parent account.
     */
    public function withParent(ChartAccount $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'type' => $parent->type,
        ]);
    }
}
