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
 * @property int|null $chart_account_id
 * @property string|null $description
 * @property bool $is_active
 * @property bool $is_system
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read ChartAccount|null $chartAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentLine> $paymentLines
 * @property-read int|null $payment_lines_count
 *
 * @method static \Database\Factories\PaymentConceptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentConcept newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentConcept newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentConcept query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentConcept active()
 *
 * @mixin \Eloquent
 */
final class PaymentConcept extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentConceptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ChartAccount, $this>
     */
    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    /**
     * @return HasMany<PaymentLine, $this>
     */
    public function paymentLines(): HasMany
    {
        return $this->hasMany(PaymentLine::class);
    }

    /**
     * Scope a query to only include active concepts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<PaymentConcept>  $query
     * @return \Illuminate\Database\Eloquent\Builder<PaymentConcept>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
