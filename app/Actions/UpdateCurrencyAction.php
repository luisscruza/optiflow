<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCurrencyAction
{
    /**
     * @param  array{name: string, symbol: string}  $data
     */
    public function handle(Currency $currency, array $data): Currency
    {
        return DB::transaction(function () use ($currency, $data): Currency {
            $currency->update([
                'name' => $data['name'],
                'symbol' => $data['symbol'],
            ]);

            return $currency;
        });
    }
}
