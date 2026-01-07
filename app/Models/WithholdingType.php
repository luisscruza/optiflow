<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property numeric $percentage
 * @property int|null $chart_account_id
 * @property string|null $description
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read ChartAccount|null $chartAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentWithholding> $paymentWithholdings
 * @property-read int|null $payment_withholdings_count
 *
 * @method static \Database\Factories\WithholdingTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WithholdingType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WithholdingType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WithholdingType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WithholdingType active()
 *
 * @mixin \Eloquent
 */
final class WithholdingType extends Model
{
    /** @use HasFactory<\Database\Factories\WithholdingTypeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ChartAccount, $this>
     */
    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    /**
     * @return HasMany<PaymentWithholding, $this>
     */
    public function paymentWithholdings(): HasMany
    {
        return $this->hasMany(PaymentWithholding::class);
    }

    /**
     * Scope a query to only include active withholding types.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WithholdingType>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WithholdingType>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate withholding amount for a given base amount.
     */
    public function calculateAmount($baseAmount): float
    {
        return round($baseAmount * ($this->percentage / 100), 2);
    }

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
