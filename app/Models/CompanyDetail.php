<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
