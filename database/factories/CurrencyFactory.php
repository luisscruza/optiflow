<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
final class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->currencyCode().'-'.fake()->numberBetween(1000, 9999);

        return [
            'name' => $code,
            'code' => $code,
            'symbol' => '$',
            'is_default' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
