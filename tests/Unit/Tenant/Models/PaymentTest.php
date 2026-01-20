<?php

declare(strict_types=1);

use App\Models\Payment;

test('to array', function (): void {
    $payment = Payment::factory()->create()->refresh();

    expect(array_keys($payment->toArray()))->toBe([
        'id',
        'payment_type',
        'payment_number',
        'bank_account_id',
        'currency_id',
        'contact_id',
        'invoice_id',
        'payment_date',
        'payment_method',
        'amount',
        'subtotal_amount',
        'tax_amount',
        'withholding_amount',
        'note',
        'status',
        'created_at',
        'updated_at',
        'status_config',
    ]);
});
