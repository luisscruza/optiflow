<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $product_id
 * @property string $type
 * @property numeric $quantity
 * @property numeric|null $unit_cost
 * @property numeric|null $total_cost
 * @property int|null $related_invoice_id
 * @property string|null $note
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read string $description
 * @property-read float $effective_quantity
 * @property-read Product $product
 * @property-read Invoice|null $relatedDocument
 * @property-read Workspace $workspace
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement betweenDates($startDate, $endDate)
 * @method static \Database\Factories\StockMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement forProduct(\App\Models\Product|int $product)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement forWorkspace(\App\Models\Workspace|int $workspace)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement incoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement outgoing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereRelatedDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUnitCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement withoutWorkspaceScope()
 *
 * @property int|null $user_id
 * @property int|null $from_workspace_id
 * @property int|null $to_workspace_id
 * @property string|null $reference_number
 * @property-read User|null $createdBy
 * @property-read Workspace|null $fromWorkspace
 * @property-read Workspace|null $toWorkspace
 *
 * @method static Builder<static>|StockMovement whereFromWorkspaceId($value)
 * @method static Builder<static>|StockMovement whereReferenceNumber($value)
 * @method static Builder<static>|StockMovement whereToWorkspaceId($value)
 * @method static Builder<static>|StockMovement whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use BelongsToWorkspace, HasFactory;

    /**
     * Get the product for this movement.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user for this movement.
     *
     * @return BelongsTo<Product, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the related document if any.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    /**
     * Get the source workspace for transfers.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function fromWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'from_workspace_id');
    }

    /**
     * Get the destination workspace for transfers.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function toWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'to_workspace_id');
    }

    /**
     * Check if this is an incoming movement.
     */
    public function isIncoming(): bool
    {
        return in_array($this->type, ['in', 'adjustment']) && $this->quantity > 0;
    }

    /**
     * Check if this is an outgoing movement.
     */
    public function isOutgoing(): bool
    {
        return in_array($this->type, ['out', 'transfer']) ||
               ($this->type === 'adjustment' && $this->quantity < 0);
    }

    /**
     * Get the effective quantity (positive for incoming, negative for outgoing).
     */
    public function getEffectiveQuantityAttribute(): float
    {
        if ($this->isOutgoing() && $this->quantity > 0) {
            return -$this->quantity;
        }

        return $this->quantity;
    }

    /**
     * Get a human-readable description of the movement.
     */
    public function getDescriptionAttribute(): string
    {
        $direction = $this->isIncoming() ? 'Added' : 'Removed';
        $quantity = abs($this->quantity);
        $productName = $this->product->name ?? 'Unknown Product';

        $description = "{$direction} {$quantity} units of {$productName}";

        if ($this->type === 'transfer') {
            $description .= ' (Transfer)';
        } elseif ($this->type === 'adjustment') {
            $description .= ' (Adjustment)';
        }

        return $description;
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (self $movement): void {
            if ($movement->unit_cost && ! $movement->total_cost) {
                $movement->total_cost = $movement->quantity * $movement->unit_cost;
            }
        });
    }

    /**
     * Scope movements by type.
     */
    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope to incoming movements.
     */
    #[Scope]
    protected function incoming(Builder $query): void
    {
        $query->whereIn('type', ['in', 'adjustment'])
            ->where('quantity', '>', 0);
    }

    /**
     * Scope to outgoing movements.
     */
    #[Scope]
    protected function outgoing(Builder $query): void
    {
        $query->whereIn('type', ['out', 'transfer'])
            ->orWhere(function ($q): void {
                $q->where('type', 'adjustment')->where('quantity', '<', 0);
            });
    }

    /**
     * Scope to movements for a specific product.
     */
    #[Scope]
    protected function forProduct(Builder $query, int|Product $product): void
    {
        $productId = $product instanceof Product ? $product->id : $product;

        $query->where('product_id', $productId);
    }

    /**
     * Scope to movements within a date range.
     */
    #[Scope]
    protected function betweenDates(Builder $query, $startDate, $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'type' => StockMovementType::class,
        ];
    }
}
