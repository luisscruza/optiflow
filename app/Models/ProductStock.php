<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductStock extends Model
{
    /** @use HasFactory<\Database\Factories\ProductStockFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'product_id',
        'workspace_id',
        'quantity',
        'minimum_quantity',
    ];

    /**
     * Get the product that owns this stock record.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if the stock is low.
     */
    public function isLow(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Check if the stock is sufficient for a required quantity.
     */
    public function isSufficient(float $requiredQuantity): bool
    {
        return $this->quantity >= $requiredQuantity;
    }

    /**
     * Increment the stock quantity.
     */
    public function incrementStock(float $quantity): bool
    {
        $this->quantity += $quantity;

        return $this->save();
    }

    /**
     * Decrement the stock quantity.
     */
    public function decrementStock(float $quantity): bool
    {
        if ($this->quantity < $quantity) {
            return false; // Insufficient stock
        }

        $this->quantity -= $quantity;

        return $this->save();
    }

    /**
     * Scope to low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_quantity');
    }

    /**
     * Scope to items with sufficient stock.
     */
    public function scopeSufficientStock($query, float $requiredQuantity = 1)
    {
        return $query->where('quantity', '>=', $requiredQuantity);
    }

    /**
     * Get the stock level status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->isLow()) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    /**
     * Get the stock level percentage compared to minimum.
     */
    public function getLevelPercentageAttribute(): float
    {
        if ($this->minimum_quantity <= 0) {
            return 100;
        }

        return round(($this->quantity / $this->minimum_quantity) * 100, 2);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'minimum_quantity' => 'decimal:2',
        ];
    }
}
