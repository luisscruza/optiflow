<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentItem>
 */
final class DocumentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = fake()->randomFloat(2, 1, 10);
        $unitPrice = $product->price;
        $discount = fake()->randomFloat(2, 0, 15); // 0-15% discount
        $tax = Tax::factory()->create();

        return [
            'document_id' => Document::factory(),
            'product_id' => $product->id,
            'description' => $product->description ?? $product->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax_id' => $tax->id,
            'tax_rate_snapshot' => $tax->rate,
            'total' => 0, // Will be auto-calculated
        ];
    }

    /**
     * Create item for a specific document.
     */
    public function forDocument(Document|int $document): static
    {
        $documentId = $document instanceof Document ? $document->id : $document;

        return $this->state(fn (array $attributes) => [
            'document_id' => $documentId,
        ]);
    }

    /**
     * Create item for a specific product.
     */
    public function forProduct(Product|int $product): static
    {
        $productModel = $product instanceof Product ? $product : Product::find($product);

        return $this->state(fn (array $attributes) => [
            'product_id' => $productModel->id,
            'description' => $productModel->description ?? $productModel->name,
            'unit_price' => $productModel->price,
        ]);
    }

    /**
     * Create item with no discount.
     */
    public function noDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => 0,
        ]);
    }

    /**
     * Create item with specific discount.
     */
    public function withDiscount(float $discount): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => $discount,
        ]);
    }

    /**
     * Create item with specific quantity.
     */
    public function withQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create item with specific unit price.
     */
    public function withUnitPrice(float $unitPrice): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_price' => $unitPrice,
        ]);
    }

    /**
     * Create item with specific tax.
     */
    public function withTax(Tax|int $tax): static
    {
        $taxModel = $tax instanceof Tax ? $tax : Tax::find($tax);

        return $this->state(fn (array $attributes) => [
            'tax_id' => $taxModel->id,
            'tax_rate_snapshot' => $taxModel->rate,
        ]);
    }

    /**
     * Create item with custom description.
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Create a service item (high price, no discount).
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => Product::factory()->service(),
            'quantity' => 1,
            'discount' => 0,
            'description' => fake()->randomElement([
                'Consulting services',
                'Installation service',
                'Support package',
                'Training session',
                'Maintenance service',
            ]),
        ]);
    }
}
