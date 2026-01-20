<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionNotFoundException;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use Illuminate\Support\Facades\DB;

final class UpdateMastertableItemAction
{
    public function handle(Mastertable $mastertable, MastertableItem $item, array $data): MastertableItem
    {
        return DB::transaction(function () use ($mastertable, $item, $data): MastertableItem {
            if ($item->mastertable_id !== $mastertable->id) {
                throw new ActionNotFoundException('Elemento no pertenece a esta tabla.');
            }

            $item->update($data);

            return $item;
        });
    }
}
