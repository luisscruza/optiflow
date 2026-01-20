<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
final class InvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->numberBetween(1, 100),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'discount_amount' => fake()->randomFloat(2, 0, 100),
            'discount_rate' => fake()->randomFloat(2, 0, 20),
            'tax_id' => Tax::factory(),
            'tax_rate' => fake()->randomFloat(2, 0, 25),
            'tax_amount' => fake()->randomFloat(2, 0, 100),
            'total' => fake()->randomFloat(2, 10, 5000),
        ];
    }

    /**
     * Indicate that the item has no discount.
     */
    public function withoutDiscount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_amount' => 0,
            'discount_rate' => 0,
        ]);
    }

    /**
     * Create an item with a specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create an item with a specific unit price.
     */
    public function withUnitPrice(float $unitPrice): static
    {
        return $this->state(fn (array $attributes): array => [
            'unit_price' => $unitPrice,
        ]);
    }
}
