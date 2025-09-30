<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\RecalculateBankAccount;
use App\Models\BankAccount;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreatePaymentAction
{
    /**
     * Execute the action.
     */
    public function handle(Invoice $invoice, array $data): void
    {
        DB::transaction(function () use ($invoice, $data): void {

            if ($invoice->amount_due < $data['amount']) {
                throw new InvalidArgumentException('Payment amount exceeds the amount due on the invoice.');
            }

            $account = BankAccount::findOrFail($data['bank_account_id']);

            $payment = $invoice->payments()->create([
                'bank_account_id' => $data['bank_account_id'],
                'currency_id' => $account->currency_id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'note' => $data['notes'] ?? null,
            ]);

            RecalculateBankAccount::dispatch($account);
        });
    }
}
