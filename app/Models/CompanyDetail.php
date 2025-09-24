<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CompanyDetail whereValue($value)
 *
 * @mixin \Eloquent
 */
final class CompanyDetail extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a company detail by key.
     */
    public static function getByKey(string $key, string $default = ''): string
    {
        return self::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a company detail by key.
     */
    public static function setByKey(string $key, string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get all company details as a key-value array.
     */
    public static function getAll(): array
    {
        return self::pluck('value', 'key')->toArray();
    }
}
