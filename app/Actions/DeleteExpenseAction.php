<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

final class DeleteExpenseAction
{
    public function handle(Expense $expense): void
    {
        DB::transaction(function () use ($expense): void {
            $expense->clearMediaCollection('attachments');
            $expense->delete();
        });
    }
}
