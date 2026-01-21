<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuotationItem>
 */
final class QuotationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quotation_id' => QuotationFactory::new(),
            'product_id' => Product::factory(),
            'description' => fake()->sentence(),
            'quantity' => fake()->randomFloat(2, 1, 10),
            'unit_price' => fake()->randomFloat(2, 10, 500),
            'subtotal' => fake()->randomFloat(2, 10, 500),
            'discount_amount' => fake()->randomFloat(2, 0, 50),
            'tax_id' => Tax::factory(),
            'tax_rate' => fake()->randomFloat(2, 0, 18),
            'total' => fake()->randomFloat(2, 10, 600),
            'tax_amount' => fake()->randomFloat(2, 0, 50),
            'discount_rate' => fake()->randomFloat(2, 0, 50),
        ];
    }
}
