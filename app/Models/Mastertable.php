<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Mastertable extends Model
{
    /**
     * @return HasMany<MastertableItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MastertableItem::class);
    }
}
