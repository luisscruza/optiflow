<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use Illuminate\Support\Facades\DB;

final class DeleteMastertableItemAction
{
    public function handle(Mastertable $mastertable, MastertableItem $item): void
    {
        DB::transaction(function () use ($mastertable, $item): void {
            if ($item->mastertable_id !== $mastertable->id) {
                throw new ActionNotFoundException('Elemento no pertenece a esta tabla.');
            }

            $item->delete();
        });
    }
}
