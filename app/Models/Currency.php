<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Currency extends Model
{
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default currency.
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get all active currencies.
     */
    public static function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)->orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Get all rates for this currency.
     */
    public function rates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class);
    }

    /**
     * Get the current rate for this currency.
     */
    public function getCurrentRate(): float
    {
        if ($this->is_default) {
            return 1.0; // Default currency always has rate of 1
        }

        $latestRate = $this->rates()
            ->where('effective_date', '<=', Carbon::now())
            ->orderBy('effective_date', 'desc')
            ->first();

        return $latestRate?->rate ?? 0.0;
    }

    /**
     * Get the rate for a specific date.
     */
    public function getRateForDate(Carbon $date): float
    {
        if ($this->is_default) {
            return 1.0;
        }

        $rate = $this->rates()
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();

        return $rate?->rate ?? 0.0;
    }

    /**
     * Format amount with currency symbol.
     */
    public function formatAmount(float $amount): string
    {
        return $this->symbol.' '.number_format($amount, 2);
    }

    /**
     * Convert amount from default currency to this currency.
     */
    public function convertFromDefault(float $amount, ?Carbon $date = null): float
    {
        $rate = $date ? $this->getRateForDate($date) : $this->getCurrentRate();

        if ($rate === 0.0) {
            return $amount;
        }

        return $amount / $rate;
    }

    /**
     * Convert amount from this currency to default currency.
     */
    public function convertToDefault(float $amount, ?Carbon $date = null): float
    {
        $rate = $date ? $this->getRateForDate($date) : $this->getCurrentRate();

        if ($rate === 0.0) {
            return $amount;
        }

        return $amount * $rate;
    }

    /**
     * Get the rate variation percentage from the previous rate.
     */
    public function getVariation(): float
    {
        if ($this->is_default) {
            return 0.0;
        }

        $latestTwoRates = $this->rates()
            ->orderBy('effective_date', 'desc')
            ->limit(2)
            ->get();

        if ($latestTwoRates->count() < 2) {
            return 0.0;
        }

        $current = $latestTwoRates->first();
        $previous = $latestTwoRates->last();

        if (! $previous || ! $current || $previous->rate === 0.0) {
            return 0.0;
        }

        $change = $current->rate - $previous->rate;
        $percentage = ($change / $previous->rate) * 100;

        return round($percentage, 2);
    }
}
