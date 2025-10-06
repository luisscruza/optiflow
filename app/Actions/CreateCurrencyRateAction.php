<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CurrencyRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class CreateCurrencyRateAction
{
    /**
     * Execute the action.
     */
    public function handle(array $validated): CurrencyRate
    {
        return DB::transaction(fn (): CurrencyRate => CurrencyRate::create([
            'currency_id' => $validated['currency_id'],
            'rate' => $validated['rate'],
            'effective_date' => Carbon::parse($validated['date'])->setTime(now()->hour, now()->minute, now()->second),
        ]));
    }
}
