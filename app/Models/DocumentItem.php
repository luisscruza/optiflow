<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_id
 * @property int $product_id
 * @property string $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $discount
 * @property int $tax_id
 * @property numeric $tax_rate_snapshot
 * @property numeric $total
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Document $document
 * @property-read float $discount_amount
 * @property-read float $effective_unit_price
 * @property-read float|null $profit
 * @property-read float|null $profit_margin
 * @property-read float $subtotal_after_discount
 * @property-read float $subtotal
 * @property-read float $tax_amount
 * @property-read Product $product
 * @property-read Tax $tax
 *
 * @method static \Database\Factories\DocumentItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem forProduct(\App\Models\Product|int $product)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereTaxRateSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentItem withDiscount()
 *
 * @mixin \Eloquent
 */
final class DocumentItem extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentItemFactory> */
    use HasFactory;

    /**
     * Get the document that owns this item.
     *
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the product for this item.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the tax for this item.
     *
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Scope to items for a specific product.
     */
    #[Scope]
    protected function forProduct(Builder $query, int|Product $product): void
    {
        $productId = $product instanceof Product ? $product->id : $product;

        $query->where('product_id', $productId);
    }

    /**
     * Scope to items with discount.
     */
    #[Scope]
    protected function withDiscount(Builder $query): void
    {
        $query->where('discount', '>', 0);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
            'discount_rate' => 'float',
            'discount_amount' => 'float',
            'tax_rate' => 'float',
            'tax_amount' => 'float',
            'total' => 'float',
        ];
    }
}
