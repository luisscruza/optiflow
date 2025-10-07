<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property BankAccountType $type
 * @property-read Currency|null $currency
 *
 * @method static \Database\Factories\BankAccountFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount query()
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 *
 * @method static Builder<static>|BankAccount onlyActive()
 *
 * @mixin \Eloquent
 */
final class BankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\BankAccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Scope to items with discount.
     */
    #[Scope]
    protected function onlyActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('is_system_account', false);
    }

    protected function casts(): array
    {
        return [
            'type' => BankAccountType::class,
        ];
    }
}
