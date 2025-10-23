<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BankAccount;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RecalculateBankAccount implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(private BankAccount $bankAccount)
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->recalculateBalance();
        } catch (InvalidArgumentException $e) {
            //

            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        //
    }

    /**
     * Recalculate the bank account balance with proper transaction handling.
     */
    private function recalculateBalance(): void
    {
        DB::transaction(function (): void {
            $lockedBankAccount = BankAccount::query()->lockForUpdate()->find($this->bankAccount->id);

            if (! $lockedBankAccount) {
                throw new InvalidArgumentException('Bank account not found during transaction');
            }

            $initialBalance = $lockedBankAccount->initial_balance;
            $initialBalanceDate = $lockedBankAccount->initial_balance_date;

            $transactionsSum = $lockedBankAccount->payments()
                ->where('payment_date', '>=', $initialBalanceDate)
                ->get()
                ->sum(fn ($payment): string => bcadd('0', (string) $payment->amount, 2));

            $newBalance = bcadd((string) $initialBalance, (string) $transactionsSum, 2);

            $lockedBankAccount->balance = (float) $newBalance;
            $lockedBankAccount->save();

        });
    }
}
