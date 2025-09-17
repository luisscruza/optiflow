<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property numeric $price
 * @property numeric|null $cost
 * @property bool $track_stock
 * @property int|null $default_tax_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Tax|null $defaultTax
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DocumentItem> $documentItems
 * @property-read int|null $document_items_count
 * @property-read float|null $profit
 * @property-read float|null $profit_margin
 * @property-read ProductStock|null $stockInCurrentWorkspace
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductStock> $stocks
 * @property-read int|null $stocks_count
 *
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product lowStock(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product tracksStock()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDefaultTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTrackStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductStock> $stocksInCurrentWorkspace
 * @property-read int|null $stocks_in_current_workspace_count
 *
 * @mixin \Eloquent
 */
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
     * Get the stock for the current workspace.
     *
     * @return HasOne<ProductStock, $this>
     */
    public function stockInCurrentWorkspace(): HasOne
    {
        return $this->hasOne(ProductStock::class)->when(
            Auth::check() && Auth::user()->current_workspace_id,
            fn ($query) => $query->where('workspace_id', Auth::user()->current_workspace_id)
        );
    }

    /**
     * Get the stock records for the current workspace.
     * This method uses a more reliable approach for checking existence.
     *
     * @return HasMany<ProductStock, $this>
     */
    public function stocksInCurrentWorkspace(): HasMany
    {
        return $this->hasMany(ProductStock::class)->when(
            Auth::check() && Auth::user()->current_workspace_id,
            fn ($query) => $query->where('workspace_id', Auth::user()->current_workspace_id)
        );
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

        return $stock ? (float) $stock->quantity : 0;
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

    /**
     * Scope to products that track stock.
     */
    #[Scope]
    protected function tracksStock(Builder $query): void
    {
        $query->where('track_stock', true);
    }

    /**
     * Scope to products with low stock in a workspace.
     */
    #[Scope]
    protected function lowStock(Builder $query, int|Workspace $workspace): void
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        $query->whereHas('stocks', function ($stockQuery) use ($workspaceId) {
            $stockQuery->where('workspace_id', $workspaceId)
                ->whereColumn('quantity', '<=', 'minimum_quantity');
        });
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
