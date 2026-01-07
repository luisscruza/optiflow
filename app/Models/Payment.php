<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property PaymentType $payment_type
 * @property string|null $payment_number
 * @property int $bank_account_id
 * @property int $currency_id
 * @property int|null $invoice_id
 * @property int|null $contact_id
 * @property \Carbon\CarbonImmutable $payment_date
 * @property PaymentMethod $payment_method
 * @property numeric $amount
 * @property numeric $subtotal_amount
 * @property numeric $tax_amount
 * @property numeric $withholding_amount
 * @property string|null $note
 * @property PaymentStatus $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read BankAccount $bankAccount
 * @property-read Currency $currency
 * @property-read Invoice|null $invoice
 * @property-read Contact|null $contact
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentLine> $lines
 * @property-read int|null $lines_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentWithholding> $withholdings
 * @property-read int|null $withholdings_count
 *
 * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment invoicePayments()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment otherIncomes()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment completed()
 *
 * @mixin \Eloquent
 */
final class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $appends = [
        'status_config',
    ];

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return HasMany<PaymentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PaymentLine::class);
    }

    /**
     * @return HasMany<PaymentWithholding, $this>
     */
    public function withholdings(): HasMany
    {
        return $this->hasMany(PaymentWithholding::class);
    }

    /**
     * Scope a query to only include invoice payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeInvoicePayments($query)
    {
        return $query->where('payment_type', PaymentType::InvoicePayment->value);
    }

    /**
     * Scope a query to only include other income payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeOtherIncomes($query)
    {
        return $query->where('payment_type', PaymentType::OtherIncome->value);
    }

    /**
     * Scope a query to only include completed payments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Payment>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Payment>
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::Completed->value);
    }

    /**
     * Check if payment is for an invoice.
     */
    public function isInvoicePayment(): bool
    {
        return $this->payment_type === PaymentType::InvoicePayment;
    }

    /**
     * Check if payment is other income.
     */
    public function isOtherIncome(): bool
    {
        return $this->payment_type === PaymentType::OtherIncome;
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }

    /**
     * Check if payment is voided.
     */
    public function isVoided(): bool
    {
        return $this->status === PaymentStatus::Voided;
    }

    /**
     * Get the status configuration for frontend display.
     *
     * @return array<string, mixed>
     */
    public function getStatusConfigAttribute(): array
    {
        return [
            'label' => $this->status->label(),
            'color' => $this->status->color(),
            'icon' => $this->status->icon(),
        ];
    }

    /**
     * Get the net amount (amount - withholdings).
     */
    public function getNetAmountAttribute(): float
    {
        return round($this->amount - $this->withholding_amount, 2);
    }

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        if ($this->isOtherIncome()) {
            $this->subtotal_amount = $this->lines->sum('subtotal');
            $this->tax_amount = $this->lines->sum('tax_amount');
            $this->withholding_amount = $this->withholdings->sum('amount');
            $this->amount = round($this->subtotal_amount + $this->tax_amount - $this->withholding_amount, 2);
        }
    }

    protected function casts(): array
    {
        return [
            'payment_type' => PaymentType::class,
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'withholding_amount' => 'decimal:2',
        ];
    }
}
