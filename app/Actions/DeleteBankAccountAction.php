<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;

final readonly class DeleteBankAccountAction
{
    public function handle(BankAccount $bankAccount): void
    {
        DB::transaction(function () use ($bankAccount): void {
            if ($bankAccount->is_system_account) {
                throw new ActionValidationException([
                    'bank_account' => 'No se puede eliminar una cuenta del sistema.',
                ]);
            }

            if ($bankAccount->payments()->count() > 0) {
                throw new ActionValidationException([
                    'bank_account' => 'No se puede eliminar una cuenta con transacciones asociadas.',
                ]);
            }

            $bankAccount->delete();
        });
    }
}
