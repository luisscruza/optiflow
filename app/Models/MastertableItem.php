<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mastertable_id
 * @property string $name
 * @property-read Mastertable $mastertable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MastertableItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MastertableItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MastertableItem query()
 *
 * @mixin \Eloquent
 */
final class MastertableItem extends Model
{
    /**
     * @return BelongsTo<Mastertable, $this>
     */
    public function mastertable(): BelongsTo
    {
        return $this->belongsTo(Mastertable::class);
    }
}
