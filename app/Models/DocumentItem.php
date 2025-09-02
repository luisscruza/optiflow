<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DocumentItem extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'discount',
        'tax_id',
        'tax_rate_snapshot',
        'total',
    ];

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
     * Calculate the line total.
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discountAmount = $subtotal * ($this->discount / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        $taxAmount = $subtotalAfterDiscount * ($this->tax_rate_snapshot / 100);

        $this->total = $subtotalAfterDiscount + $taxAmount;
    }

    /**
     * Get the line subtotal (before discount and tax).
     */
    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Get the discount amount.
     */
    public function getDiscountAmountAttribute(): float
    {
        return $this->subtotal * ($this->discount / 100);
    }

    /**
     * Get the subtotal after discount.
     */
    public function getSubtotalAfterDiscountAttribute(): float
    {
        return $this->subtotal - $this->discount_amount;
    }

    /**
     * Get the tax amount.
     */
    public function getTaxAmountAttribute(): float
    {
        return $this->subtotal_after_discount * ($this->tax_rate_snapshot / 100);
    }

    /**
     * Get the effective unit price after discount.
     */
    public function getEffectiveUnitPriceAttribute(): float
    {
        return $this->unit_price * (1 - $this->discount / 100);
    }

    /**
     * Scope to items for a specific product.
     */
    public function scopeForProduct($query, int|Product $product)
    {
        $productId = $product instanceof Product ? $product->id : $product;

        return $query->where('product_id', $productId);
    }

    /**
     * Scope to items with discount.
     */
    public function scopeWithDiscount($query)
    {
        return $query->where('discount', '>', 0);
    }

    /**
     * Check if this item has a discount.
     */
    public function hasDiscount(): bool
    {
        return $this->discount > 0;
    }

    /**
     * Get the profit for this line item.
     */
    public function getProfitAttribute(): ?float
    {
        if (! $this->product->cost) {
            return null;
        }

        $costTotal = $this->quantity * $this->product->cost;

        return $this->subtotal_after_discount - $costTotal;
    }

    /**
     * Get the profit margin percentage for this line item.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->product->cost || $this->product->cost <= 0) {
            return null;
        }

        $costTotal = $this->quantity * $this->product->cost;

        if ($costTotal <= 0) {
            return null;
        }

        return round((($this->subtotal_after_discount - $costTotal) / $costTotal) * 100, 2);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::saving(function (self $item) {
            // Auto-calculate total
            $item->calculateTotal();

            // Auto-set tax rate snapshot from tax if not provided
            if (! $item->tax_rate_snapshot && $item->tax) {
                $item->tax_rate_snapshot = $item->tax->rate;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate_snapshot' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
