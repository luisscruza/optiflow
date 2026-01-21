<?php

declare(strict_types=1);

use App\Models\Currency;
use Carbon\Carbon;
use Database\Factories\CurrencyRateFactory;

test('to array', function (): void {
    $currency = Currency::factory()->create()->refresh();

    expect(array_keys($currency->toArray()))->toBe([
        'id',
        'name',
        'code',
        'symbol',
        'is_default',
        'is_active',
        'created_at',
        'updated_at',
    ]);
});

test('handles rate calculations', function (): void {
    $currency = Currency::factory()->create([
        'is_default' => false,
        'symbol' => '€',
    ]);

    CurrencyRateFactory::new()->create([
        'currency_id' => $currency->id,
        'rate' => 1.5,
        'effective_date' => Carbon::now()->subDays(2),
    ]);

    CurrencyRateFactory::new()->create([
        'currency_id' => $currency->id,
        'rate' => 2.0,
        'effective_date' => Carbon::now()->subDay(),
    ]);

    expect($currency->getCurrentRate())->toBe(2.0)
        ->and($currency->getRateForDate(Carbon::now()->subDays(2)))->toBe(1.5)
        ->and($currency->formatAmount(12.5))->toBe('€ 12.50')
        ->and($currency->convertFromDefault(10))->toBe(5.0)
        ->and($currency->convertToDefault(10))->toBe(20.0)
        ->and($currency->getVariation())->toBe(33.33);
});
