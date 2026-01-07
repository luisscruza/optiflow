<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tax>
 */
final class TaxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rates = [18.00, 21.00, 23.00, 15.00, 10.00, 5.00];
        $rate = fake()->randomElement($rates);

        return [
            'name' => 'IVA ' . $rate . '%',
            'type' => fake()->randomElement(TaxType::cases()),
            'rate' => $rate,
            'is_default' => false,
        ];
    }

    /**
     * Indicate that the tax is the default tax.
     */
    public function default(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_default' => true,
        ]);
    }

    /**
     * Create a specific tax rate.
     */
    public function withRate(float $rate): static
    {
        return $this->state(fn(array $attributes): array => [
            'name' => 'IVA ' . $rate . '%',
            'rate' => $rate,
        ]);
    }

    /**
     * Create a tax of a specific type.
     */
    public function ofType(TaxType $type): static
    {
        return $this->state(fn(array $attributes): array => [
            'type' => $type,
        ]);
    }
}
