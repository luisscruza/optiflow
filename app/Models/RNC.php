<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $identification
 * @property string|null $name
 * @property string|null $comercial_name
 * @property string|null $status
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereComercialName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereIdentification($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RNC whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class RNC extends Model
{
    public $incrementing = false;

    protected $table = 'rncs';

    protected $primaryKey = 'identification';

    protected $keyType = 'string';
}
