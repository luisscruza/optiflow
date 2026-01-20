<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCurrencyAction
{
    public function handle(Currency $currency): void
    {
        DB::transaction(function () use ($currency): void {
            if ($currency->is_default) {
                throw new ActionValidationException([
                    'currency' => 'No se puede eliminar la moneda predeterminada.',
                ]);
            }

            $currency->delete();
        });
    }
}
