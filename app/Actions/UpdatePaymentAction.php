<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\RecalculateBankAccount;
use App\Models\BankAccount;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdatePaymentAction
{
    /**
     * Execute the action.
     */
    public function handle(Payment $payment, array $data): void
    {
        DB::transaction(function () use ($payment, $data): void {
            $invoice = $payment->invoice;

            // Calculate the new amount due considering this payment update
            $otherPaymentsTotal = $invoice->payments()
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            $newAmountDue = $invoice->total_amount - $otherPaymentsTotal;

            if ($newAmountDue < $data['amount']) {
                throw new InvalidArgumentException('Payment amount exceeds the amount due on the invoice.');
            }

            $oldBankAccount = $payment->bankAccount;
            $newBankAccount = BankAccount::query()->findOrFail($data['bank_account_id']);

            $payment->update([
                'bank_account_id' => $data['bank_account_id'],
                'currency_id' => $newBankAccount->currency_id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'note' => $data['notes'] ?? null,
            ]);

            // Recalculate both old and new bank accounts if they changed
            if ($oldBankAccount->id !== $newBankAccount->id) {
                RecalculateBankAccount::dispatch($oldBankAccount);
            }
            RecalculateBankAccount::dispatch($newBankAccount);
        });
    }
}
