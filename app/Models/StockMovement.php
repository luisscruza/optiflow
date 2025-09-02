<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'workspace_id',
        'product_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'related_document_id',
        'note',
    ];

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
     * Get the related document if any.
     *
     * @return BelongsTo<Document, $this>
     */
    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'related_document_id');
    }

    /**
     * Scope movements by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to incoming movements.
     */
    public function scopeIncoming($query)
    {
        return $query->whereIn('type', ['in', 'adjustment'])
            ->where('quantity', '>', 0);
    }

    /**
     * Scope to outgoing movements.
     */
    public function scopeOutgoing($query)
    {
        return $query->whereIn('type', ['out', 'transfer'])
            ->orWhere(function ($q) {
                $q->where('type', 'adjustment')->where('quantity', '<', 0);
            });
    }

    /**
     * Scope to movements for a specific product.
     */
    public function scopeForProduct($query, int|Product $product)
    {
        $productId = $product instanceof Product ? $product->id : $product;

        return $query->where('product_id', $productId);
    }

    /**
     * Scope to movements within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
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

        self::creating(function (self $movement) {
            // Auto-calculate total_cost if not provided
            if ($movement->unit_cost && ! $movement->total_cost) {
                $movement->total_cost = $movement->quantity * $movement->unit_cost;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
}
