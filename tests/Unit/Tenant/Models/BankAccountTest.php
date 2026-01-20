<?php

declare(strict_types=1);

use App\Enums\BankAccountType;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Payment;

test('to array', function (): void {
    $bankAccount = BankAccount::factory()->create()->refresh();

    expect(array_keys($bankAccount->toArray()))
        ->toBe([
            'id',
            'name',
            'type',
            'currency_id',
            'account_number',
            'initial_balance',
            'initial_balance_date',
            'description',
            'created_at',
            'updated_at',
            'is_system_account',
            'is_active',
            'balance',
        ]);
});
