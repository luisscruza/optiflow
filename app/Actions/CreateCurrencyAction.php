<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class CreateCurrencyAction
{
    /**
     * Execute the action.
     */
    public function handle(array $validated): Currency
    {
        return DB::transaction(function () use ($validated): Currency {
            $currency = Currency::create([
                'name' => $validated['name'],
                'code' => mb_strtoupper($validated['code']),
                'symbol' => $validated['symbol'],
                'is_default' => false,
                'is_active' => true,
            ]);

            // Create initial rate
            CurrencyRate::create([
                'currency_id' => $currency->id,
                'rate' => $validated['initial_rate'],
                'effective_date' => Carbon::now(),
            ]);

            return $currency;
        });
    }
}
