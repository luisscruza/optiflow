<?php

declare(strict_types=1);

use App\Models\PaymentLine;

test('to array', function (): void {
    $paymentLine = PaymentLine::factory()->create()->refresh();

    expect(array_keys($paymentLine->toArray()))->toBe([
        'id',
        'payment_id',
        'chart_account_id',
        'payment_concept_id',
        'description',
        'quantity',
        'unit_price',
        'subtotal',
        'tax_amount',
        'tax_id',
        'total',
        'sort_order',
        'created_at',
        'updated_at',
    ]);
});
