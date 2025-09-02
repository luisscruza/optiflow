<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductStock>
 */
final class ProductStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minimumQuantity = fake()->numberBetween(5, 20);
        $currentQuantity = fake()->numberBetween(0, 100);

        return [
            'product_id' => Product::factory(),
            'workspace_id' => Workspace::factory(),
            'quantity' => $currentQuantity,
            'minimum_quantity' => $minimumQuantity,
        ];
    }

    /**
     * Indicate that the stock is low.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minimumQuantity = fake()->numberBetween(10, 20);

            return [
                'minimum_quantity' => $minimumQuantity,
                'quantity' => fake()->numberBetween(0, $minimumQuantity),
            ];
        });
    }

    /**
     * Indicate that the stock is out.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }

    /**
     * Indicate that the stock is well stocked.
     */
    public function wellStocked(): static
    {
        return $this->state(function (array $attributes) {
            $minimumQuantity = fake()->numberBetween(5, 15);

            return [
                'minimum_quantity' => $minimumQuantity,
                'quantity' => fake()->numberBetween($minimumQuantity * 2, $minimumQuantity * 10),
            ];
        });
    }

    /**
     * Create stock for a specific product and workspace.
     */
    public function forProductAndWorkspace(Product|int $product, Workspace|int $workspace): static
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
            'workspace_id' => $workspaceId,
        ]);
    }

    /**
     * Create stock with specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create stock with specific minimum quantity.
     */
    public function withMinimumQuantity(float $minimumQuantity): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_quantity' => $minimumQuantity,
        ]);
    }
}
