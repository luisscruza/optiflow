<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionException;
use App\Models\Salesman;
use Illuminate\Support\Facades\DB;

final readonly class DeleteSalesmanAction
{
    public function handle(Salesman $salesman): void
    {
        DB::transaction(function () use ($salesman): void {
            if ($salesman->invoices()->exists()) {
                throw new ActionException('No se puede eliminar un vendedor que tiene facturas asociadas.');
            }
            $salesman->delete();
        });
    }
}
