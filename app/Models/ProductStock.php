<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $workspace_id
 * @property numeric $quantity
 * @property numeric $minimum_quantity
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read float $level_percentage
 * @property-read string $status
 * @property-read Product $product
 * @property-read Workspace $workspace
 *
 * @method static \Database\Factories\ProductStockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock lowStock()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock sufficientStock(float $requiredQuantity = 1)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereMinimumQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock withoutWorkspaceScope()
 *
 * @property int|null $supplier_id
 * @property-read Contact|null $supplier
 *
 * @method static Builder<static>|ProductStock whereSupplierId($value)
 *
 * @mixin \Eloquent
 */
final class ProductStock extends Model
{
    /** @use HasFactory<\Database\Factories\ProductStockFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'product_id',
        'workspace_id',
        'supplier_id',
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
     * Get the supplier for this stock record.
     *
     * @return BelongsTo<Contact, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
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

    /**
     * Scope to low stock items.
     */
    #[Scope]
    protected function lowStock(Builder $query): void
    {
        $query->whereColumn('quantity', '<=', 'minimum_quantity');
    }

    /**
     * Scope to items with sufficient stock.
     */
    #[Scope]
    protected function sufficientStock(Builder $query, float $requiredQuantity = 1): void
    {
        $query->where('quantity', '>=', $requiredQuantity);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'minimum_quantity' => 'decimal:2',
        ];
    }
}
