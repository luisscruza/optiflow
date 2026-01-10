<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\RecalculateBankAccount;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

final readonly class DeletePaymentAction
{
    /**
     * Execute the action.
     */
    public function handle(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $bankAccount = $payment->bankAccount;

            $payment->delete();

            if ($payment->invoice) {
                $payment->invoice->updatePaymentStatus();
            }

            RecalculateBankAccount::dispatch($bankAccount);
        });
    }
}
