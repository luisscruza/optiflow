<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductInventoryAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductInventoryAdjustmentItem>
 */
final class ProductInventoryAdjustmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 50);
        $currentQuantity = fake()->randomFloat(2, 0, 100);
        $adjustmentType = fake()->randomElement(['increment', 'decrement']);
        $finalQuantity = $adjustmentType === 'increment'
            ? $currentQuantity + $quantity
            : max(0, $currentQuantity - $quantity);
        $averageCost = fake()->randomFloat(2, 1, 500);

        return [
            'product_inventory_adjustment_id' => ProductInventoryAdjustment::factory(),
            'product_id' => Product::factory()->tracksStock(),
            'adjustment_type' => $adjustmentType,
            'quantity' => $quantity,
            'current_quantity' => $currentQuantity,
            'final_quantity' => $finalQuantity,
            'average_cost' => $averageCost,
            'total_adjusted' => round($quantity * $averageCost, 2),
        ];
    }
}
