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
 * @property int $invoice_id
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
 * @property-read Invoice $invoice
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem forProduct(\App\Models\Product|int $product)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTaxRateSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceItem withDiscount()
 *
 * @property float $tax_rate
 * @property float|null $discount_rate
 *
 * @method static Builder<static>|InvoiceItem whereDiscountAmount($value)
 * @method static Builder<static>|InvoiceItem whereDiscountRate($value)
 * @method static Builder<static>|InvoiceItem whereTaxAmount($value)
 * @method static Builder<static>|InvoiceItem whereTaxRate($value)
 *
 * @mixin \Eloquent
 */
final class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * Get the document that owns this item.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    public function getDiscountAmountAttribute(): float
    {
        $discountRate = $this->discount_rate ?? 0;
        $discountMultiplier = (100 - $discountRate) / 100;

        return ($this->quantity * $this->unit_price) * (1 - $discountMultiplier);
    }

    public function getSubtotalAttribute(): float
    {
        $discountRate = $this->discount_rate ?? 0;
        $discountMultiplier = (100 - $discountRate) / 100;
        $taxAmount = $this->tax_amount ?? 0;

        return ($this->quantity * $this->unit_price) * $discountMultiplier + $taxAmount;
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
