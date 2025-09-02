<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 5, 100);
        $markup = fake()->randomFloat(2, 1.3, 3.0); // 30% to 200% markup

        return [
            'name' => fake()->words(2, true),
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}'),
            'description' => fake()->sentence(),
            'price' => round($cost * $markup, 2),
            'cost' => $cost,
            'track_stock' => fake()->boolean(80), // 80% chance of tracking stock
            'default_tax_id' => Tax::factory(),
        ];
    }

    /**
     * Indicate that the product tracks stock.
     */
    public function tracksStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_stock' => true,
        ]);
    }

    /**
     * Indicate that the product doesn't track stock.
     */
    public function doesntTrackStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'track_stock' => false,
        ]);
    }

    /**
     * Create a service product (no stock tracking, no cost).
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement([
                'Consulting', 'Support', 'Installation', 'Training', 'Maintenance',
            ]).' Service',
            'track_stock' => false,
            'cost' => null,
            'price' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Create a physical product with specific price range.
     */
    public function withPriceRange(float $min, float $max): static
    {
        $cost = fake()->randomFloat(2, $min * 0.4, $max * 0.7);

        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
            'price' => fake()->randomFloat(2, $min, $max),
            'track_stock' => true,
        ]);
    }

    /**
     * Create a product with specific tax.
     */
    public function withTax(Tax|int $tax): static
    {
        $taxId = $tax instanceof Tax ? $tax->id : $tax;

        return $this->state(fn (array $attributes) => [
            'default_tax_id' => $taxId,
        ]);
    }
}
