<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\CurrencyRate;
use Carbon\Carbon;
use Database\Factories\CurrencyRateFactory;

test('to array', function (): void {
    $rate = CurrencyRateFactory::new()->create()->refresh();

    expect(array_keys($rate->toArray()))->toBe([
        'id',
        'currency_id',
        'rate',
        'effective_date',
        'created_at',
        'updated_at',
    ]);
});

test('fetches and updates rates', function (): void {
    $currency = Currency::factory()->create();

    CurrencyRateFactory::new()->create([
        'currency_id' => $currency->id,
        'rate' => 1.1,
        'effective_date' => Carbon::now()->subDays(3),
    ]);

    $latest = CurrencyRateFactory::new()->create([
        'currency_id' => $currency->id,
        'rate' => 1.4,
        'effective_date' => Carbon::now()->subDay(),
    ]);

    $rate = CurrencyRate::getLatestRateForCurrency($currency->id, Carbon::now());

    expect($rate)->not->toBeNull()
        ->and($rate?->id)->toBe($latest->id);

    $updated = CurrencyRate::setRateForDate($currency->id, 1.8, Carbon::now()->subDays(2));

    expect($updated->rate)->toBe(1.8);

    $history = CurrencyRate::getHistoricalRates(
        $currency->id,
        Carbon::now()->subDays(4),
        Carbon::now()
    );

    expect($history)->not->toBeEmpty();
});
