<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Mastertable;
use App\Models\MastertableItem;
use Illuminate\Support\Facades\DB;

final class CreateMastertableItemAction
{
    public function handle(Mastertable $mastertable, array $data): MastertableItem
    {
        return DB::transaction(function () use ($mastertable, $data): MastertableItem {
            return $mastertable->items()->create($data);
        });
    }
}
