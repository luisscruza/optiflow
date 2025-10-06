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
        $quantity = fake()->numberBetween(1, 100);
        $unitPrice = fake()->randomFloat(2, 10, 1000);
        $discountRate = fake()->randomFloat(2, 0, 20);
        $tax = Tax::factory()->create();
        $taxRate = (float) $tax->rate;

        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discountRate / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $subtotalAfterDiscount * ($taxRate / 100);
        $total = $subtotalAfterDiscount + $taxAmount;

        return [
            'invoice_id' => Invoice::factory(),
            'product_id' => Product::factory(),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $discountAmount,
            'discount_rate' => $discountRate,
            'tax_id' => $tax->id,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * Indicate that the item has no discount.
     */
    public function withoutDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_amount' => 0,
            'discount_rate' => 0,
        ]);
    }

    /**
     * Create an item with a specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create an item with a specific unit price.
     */
    public function withUnitPrice(float $unitPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
        ]);
    }
}
