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

test('belongs to currency', function (): void {
    $currency = Currency::factory()->create();
    $bankAccount = BankAccount::factory()->create(['currency_id' => $currency->id]);

    expect($bankAccount->currency)->toBeInstanceOf(Currency::class);
    expect($bankAccount->currency->id)->toBe($currency->id);
});

test('has many payments', function (): void {
    $bankAccount = BankAccount::factory()->create();
    Payment::factory()->count(3)->create(['bank_account_id' => $bankAccount->id]);

    expect($bankAccount->payments)->toHaveCount(3);
    expect($bankAccount->payments->first())->toBeInstanceOf(Payment::class);
});

test('only active scope filters correctly', function (): void {
    BankAccount::factory()->create(['is_active' => true, 'is_system_account' => false]);
    BankAccount::factory()->create(['is_active' => false, 'is_system_account' => false]);
    BankAccount::factory()->create(['is_active' => true, 'is_system_account' => true]);

    $activeAccounts = BankAccount::onlyActive()->get();

    expect($activeAccounts)->toHaveCount(1);
});

test('casts type to BankAccountType enum', function (): void {
    $bankAccount = BankAccount::factory()->create(['type' => BankAccountType::Bank]);

    expect($bankAccount->type)->toBeInstanceOf(BankAccountType::class);
    expect($bankAccount->type)->toBe(BankAccountType::Bank);
});
