<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property BankAccountType $type
 * @property-read Currency|null $currency
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankAccount query()
 *
 * @mixin \Eloquent
 */
final class BankAccount extends Model
{
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
