<?php

declare(strict_types=1);

use App\Models\PaymentWithholding;

test('to array', function (): void {
    $paymentWithholding = PaymentWithholding::factory()->create()->refresh();

    expect(array_keys($paymentWithholding->toArray()))->toBe([
        'id',
        'payment_id',
        'withholding_type_id',
        'base_amount',
        'percentage',
        'amount',
        'created_at',
        'updated_at',
    ]);
});
