<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CurrencyRate extends Model
{
    protected $fillable = [
        'currency_id',
        'rate',
        'effective_date',
    ];

    protected $casts = [
        'rate' => 'float',
        'effective_date' => 'datetime',
    ];

    /**
     * Get the latest rate for a currency on or before a specific date.
     */
    public static function getLatestRateForCurrency(int $currencyId, ?Carbon $date = null): ?self
    {
        $date = $date ?: Carbon::now();

        return self::where('currency_id', $currencyId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    /**
     * Create or update a rate for a currency on a specific date.
     */
    public static function setRateForDate(int $currencyId, float $rate, Carbon $date): self
    {
        return self::updateOrCreate(
            [
                'currency_id' => $currencyId,
                'effective_date' => $date,
            ],
            [
                'rate' => $rate,
            ]
        );
    }

    /**
     * Get historical rates for a currency within a date range.
     */
    public static function getHistoricalRates(int $currencyId, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('currency_id', $currencyId)
            ->whereBetween('effective_date', [$startDate, $endDate])
            ->orderBy('effective_date')
            ->get();
    }

    /**
     * Get the currency that owns this rate.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Calculate rate variation from previous rate.
     */
    public function getVariation(): array
    {
        $previousRate = self::where('currency_id', $this->currency_id)
            ->where('effective_date', '<', $this->effective_date)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (! $previousRate) {
            return [
                'absolute' => 0,
                'percentage' => 0,
            ];
        }

        $absolute = $this->rate - $previousRate->rate;
        $percentage = ($absolute / $previousRate->rate) * 100;

        return [
            'absolute' => round($absolute, 4),
            'percentage' => round($percentage, 2),
        ];
    }
}
