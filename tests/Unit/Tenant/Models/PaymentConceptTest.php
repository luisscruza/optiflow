<?php

declare(strict_types=1);

use App\Models\PaymentConcept;

test('to array', function (): void {
    $paymentConcept = PaymentConcept::factory()->create()->refresh();

    expect(array_keys($paymentConcept->toArray()))->toBe([
        'id',
        'name',
        'code',
        'chart_account_id',
        'description',
        'is_active',
        'is_system',
        'created_at',
        'updated_at',
    ]);
});
