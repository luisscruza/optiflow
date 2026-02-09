<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductInventoryAdjustmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\ProductInventoryAdjustmentItemFactory> */
    use HasFactory;

    protected $fillable = [
        'product_inventory_adjustment_id',
        'product_id',
        'adjustment_type',
        'quantity',
        'current_quantity',
        'final_quantity',
        'average_cost',
        'total_adjusted',
    ];

    /**
     * @return BelongsTo<ProductInventoryAdjustment, $this>
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(ProductInventoryAdjustment::class, 'product_inventory_adjustment_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'current_quantity' => 'decimal:2',
            'final_quantity' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'total_adjusted' => 'decimal:2',
        ];
    }
}
