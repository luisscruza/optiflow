<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BankAccount;

final readonly class UpdateBankAccountAction
{
    /**
     * Update an existing bank account.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(BankAccount $bankAccount, array $data): BankAccount
    {
        $bankAccount->update([
            'name' => $data['name'],
            'type' => $data['type'],
            'currency_id' => $data['currency_id'],
            'account_number' => $data['account_number'] ?? null,
            'description' => $data['description'] ?? '',
            'is_active' => $data['is_active'] ?? $bankAccount->is_active,
        ]);

        return $bankAccount->fresh();
    }
}
