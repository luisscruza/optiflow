<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $quotation_id
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
 * @property-read Quotation $quotation
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem forProduct(\App\Models\Product|int $product)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereTaxRateSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|QuotationItem withDiscount()
 *
 * @property float $tax_rate
 * @property float|null $discount_rate
 *
 * @method static Builder<static>|QuotationItem whereDiscountAmount($value)
 * @method static Builder<static>|QuotationItem whereDiscountRate($value)
 * @method static Builder<static>|QuotationItem whereTaxAmount($value)
 * @method static Builder<static>|QuotationItem whereTaxRate($value)
 *
 * @mixin \Eloquent
 */
final class QuotationItem extends Model
{
    use HasFactory;

    /**
     * Get the quotation that owns this item.
     *
     * @return BelongsTo<Quotation, $this>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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

    /** (legacy single tax - kept for backwards compatibility).
     *
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Get all taxes for this item (many-to-many).
     *
     * @return BelongsToMany<Tax, $this>
     */
    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'quotation_item_tax')
            ->withPivot(['rate', 'amount'])
            ->withTimestamps();
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_price' => 'float',
            'subtotal' => 'float',
            'discount_rate' => 'float',
            'discount_amount' => 'float',
            'tax_rate' => 'float',
            'tax_amount' => 'float',
            'total' => 'float',
        ];
    }
}
