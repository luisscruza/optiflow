<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\InvoiceStatus;
use App\Exceptions\ActionValidationException;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

final class DeleteInvoiceAction
{
    public function handle(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            if (! $invoice->canBeDeleted()) {
                throw new ActionValidationException([
                    'error' => 'La factura no se puede eliminar porque tiene pagos registrados.',
                ]);
            }

            $invoice->update([
                'status' => InvoiceStatus::Deleted->value,
            ]);
        });
    }
}
