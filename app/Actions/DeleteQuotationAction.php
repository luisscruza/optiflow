<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

final class DeleteQuotationAction
{
    public function handle(Quotation $quotation): void
    {
        DB::transaction(function () use ($quotation): void {
            if ($quotation->status !== 'draft') {
                throw new ActionValidationException([
                    'error' => 'Solo se pueden eliminar cotizaciones en borrador.',
                ]);
            }

            $quotation->delete();
        });
    }
}
