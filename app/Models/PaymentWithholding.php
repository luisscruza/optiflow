<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payment_id
 * @property int $withholding_type_id
 * @property numeric $base_amount
 * @property numeric $percentage
 * @property numeric $amount
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Payment $payment
 * @property-read WithholdingType $withholdingType
 *
 * @method static \Database\Factories\PaymentWithholdingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentWithholding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentWithholding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentWithholding query()
 *
 * @mixin \Eloquent
 */
final class PaymentWithholding extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentWithholdingFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<WithholdingType, $this>
     */
    public function withholdingType(): BelongsTo
    {
        return $this->belongsTo(WithholdingType::class);
    }

    /**
     * Calculate and set the withholding amount based on base amount and percentage.
     */
    public function calculateAmount(): void
    {
        $this->amount = round($this->base_amount * ($this->percentage / 100), 2);
    }

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }
}
