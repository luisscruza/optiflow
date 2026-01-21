<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
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

test('reports status helpers', function (): void {
    $payment = Payment::factory()->create([
        'payment_type' => PaymentType::InvoicePayment,
        'status' => PaymentStatus::Completed,
        'amount' => 120.0,
        'withholding_amount' => 20.0,
    ]);

    expect($payment->isInvoicePayment())->toBeTrue()
        ->and($payment->isOtherIncome())->toBeFalse()
        ->and($payment->isCompleted())->toBeTrue()
        ->and($payment->isVoided())->toBeFalse()
        ->and($payment->net_amount)->toBe(100.0);

    $payment->status = PaymentStatus::Voided;
    $payment->save();

    expect($payment->fresh()->isVoided())->toBeTrue();
});
