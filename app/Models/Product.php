<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'price',
        'cost',
        'track_stock',
        'default_tax_id',
    ];

    /**
     * Get the default tax for this product.
     *
     * @return BelongsTo<Tax, $this>
     */
    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    /**
     * Get the stock records for this product.
     *
     * @return HasMany<ProductStock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get the stock movements for this product.
     *
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the document items for this product.
     *
     * @return HasMany<DocumentItem, $this>
     */
    public function documentItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    /**
     * Get the current stock for a specific workspace.
     */
    public function getStockForWorkspace(int|Workspace $workspace): ?ProductStock
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->stocks()->where('workspace_id', $workspaceId)->first();
    }

    /**
     * Get the current stock quantity for a specific workspace.
     */
    public function getStockQuantityForWorkspace(int|Workspace $workspace): float
    {
        $stock = $this->getStockForWorkspace($workspace);

        return $stock ? $stock->quantity : 0;
    }

    /**
     * Check if the product has sufficient stock in a workspace.
     */
    public function hasSufficientStock(int|Workspace $workspace, float $requiredQuantity): bool
    {
        if (! $this->track_stock) {
            return true;
        }

        return $this->getStockQuantityForWorkspace($workspace) >= $requiredQuantity;
    }

    /**
     * Scope to products that track stock.
     */
    public function scopeTracksStock($query)
    {
        return $query->where('track_stock', true);
    }

    /**
     * Scope to products with low stock in a workspace.
     */
    public function scopeLowStock($query, int|Workspace $workspace)
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $query->whereHas('stocks', function ($stockQuery) use ($workspaceId) {
            $stockQuery->where('workspace_id', $workspaceId)
                ->whereColumn('quantity', '<=', 'minimum_quantity');
        });
    }

    /**
     * Get the profit margin percentage.
     */
    public function getProfitMarginAttribute(): ?float
    {
        if (! $this->cost || $this->cost <= 0) {
            return null;
        }

        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }

    /**
     * Get the profit amount.
     */
    public function getProfitAttribute(): ?float
    {
        if (! $this->cost) {
            return null;
        }

        return $this->price - $this->cost;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'track_stock' => 'boolean',
        ];
    }
}
