<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['in', 'out']);
        $quantity = fake()->randomFloat(2, 1, 50);
        $unitCost = fake()->randomFloat(2, 5, 100);

        return [
            'workspace_id' => Workspace::factory(),
            'product_id' => Product::factory(),
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'related_invoice_id' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that this is an incoming movement.
     */
    public function incoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'in',
            'note' => 'Stock added to inventory',
        ]);
    }

    /**
     * Indicate that this is an outgoing movement.
     */
    public function outgoing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'out',
            'note' => 'Stock removed from inventory',
        ]);
    }

    /**
     * Indicate that this is an adjustment.
     */
    public function adjustment(): static
    {
        return $this->state(function (array $attributes): array {
            $isPositive = fake()->boolean();

            return [
                'type' => 'adjustment',
                'quantity' => $isPositive ? abs($attributes['quantity']) : -abs($attributes['quantity']),
                'note' => $isPositive ? 'Stock adjustment - increase' : 'Stock adjustment - decrease',
            ];
        });
    }

    /**
     * Indicate that this is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'transfer',
            'note' => 'Stock transferred between workspaces',
        ]);
    }

    /**
     * Create movement for a specific product and workspace.
     */
    public function forProductAndWorkspace(Product|int $product, Workspace|int $workspace): static
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->state(fn (array $attributes): array => [
            'product_id' => $productId,
            'workspace_id' => $workspaceId,
        ]);
    }

    /**
     * Create movement related to a invoice.
     */
    public function forInvoice(Invoice|int $invoice): static
    {
        $invoiceId = $invoice instanceof Invoice ? $invoice->id : $invoice;

        return $this->state(fn (array $attributes): array => [
            'related_invoice_id' => $invoiceId,
            'type' => 'out', // Usually invoices reduce stock
            'note' => 'Stock movement from invoice #'.$invoiceId,
        ]);
    }

    /**
     * Create movement with specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity): array {
            $unitCost = $attributes['unit_cost'] ?? fake()->randomFloat(2, 5, 100);

            return [
                'quantity' => $quantity,
                'total_cost' => $quantity * $unitCost,
            ];
        });
    }

    /**
     * Create movement with specific cost.
     */
    public function withCost(float $unitCost): static
    {
        return $this->state(function (array $attributes) use ($unitCost): array {
            $quantity = $attributes['quantity'] ?? fake()->randomFloat(2, 1, 50);

            return [
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
            ];
        });
    }
}
