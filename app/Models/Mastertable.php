<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MastertableItem> $items
 * @property-read int|null $items_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mastertable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mastertable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Mastertable query()
 *
 * @mixin \Eloquent
 */
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
