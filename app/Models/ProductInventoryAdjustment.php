<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductInventoryAdjustment extends Model
{
    /** @use HasFactory<\Database\Factories\ProductInventoryAdjustmentFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'adjustment_date',
        'notes',
        'total_adjusted',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<ProductInventoryAdjustmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductInventoryAdjustmentItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'total_adjusted' => 'decimal:2',
        ];
    }
}
