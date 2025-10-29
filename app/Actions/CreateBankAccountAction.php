<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

final readonly class CreateBankAccountAction
{
    /**
     * Execute the action.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(array $validated): BankAccount
    {
        return DB::transaction(function () use ($validated): BankAccount {
            $bankAccount = BankAccount::query()->create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'currency_id' => $validated['currency_id'],
                'account_number' => $validated['account_number'] ?? null,
                'initial_balance' => $validated['initial_balance'],
                'initial_balance_date' => $validated['initial_balance_date'],
                'description' => $validated['description'] ?? '',
                'balance' => $validated['initial_balance'],
                'is_system_account' => false,
                'is_active' => true,
            ]);

            return $bankAccount;
        });
    }
}
