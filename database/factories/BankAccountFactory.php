<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BankAccountType;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
final class BankAccountFactory extends Factory
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
            'type' => fake()->randomElement(BankAccountType::cases()),
            'currency_id' => Currency::factory(),
            'account_number' => fake()->optional()->numerify('####-####-####'),
            'initial_balance' => fake()->randomFloat(2, 0, 100000),
            'initial_balance_date' => now()->subDays(fake()->numberBetween(1, 365)),
            'description' => fake()->sentence(),
            'is_system_account' => false,
            'is_active' => true,
            'balance' => fake()->randomFloat(2, 0, 100000),
        ];
    }

    /**
     * Indicate that the bank account is a system account.
     */
    public function systemAccount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_system_account' => true,
        ]);
    }

    /**
     * Indicate that the bank account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
