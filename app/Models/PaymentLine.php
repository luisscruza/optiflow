<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $payment_id
 * @property int|null $chart_account_id
 * @property int|null $payment_concept_id
 * @property string|null $description
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $tax_amount
 * @property int|null $tax_id
 * @property numeric $total
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Payment $payment
 * @property-read ChartAccount|null $chartAccount
 * @property-read PaymentConcept|null $paymentConcept
 * @property-read Tax|null $tax
 *
 * @method static \Database\Factories\PaymentLineFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentLine query()
 *
 * @mixin \Eloquent
 */
final class PaymentLine extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<ChartAccount, $this>
     */
    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }

    /**
     * @return BelongsTo<PaymentConcept, $this>
     */
    public function paymentConcept(): BelongsTo
    {
        return $this->belongsTo(PaymentConcept::class);
    }

    /**
     * @return BelongsTo<Tax, $this>
     */
    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    /**
     * Calculate the subtotal (quantity * unit_price).
     */
    public function getSubtotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    /**
     * Recalculate and set the total.
     */
    public function calculateTotal(): void
    {
        $this->total = round($this->subtotal + $this->tax_amount, 2);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }
}
