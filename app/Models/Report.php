<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportGroup;
use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property ReportType $type
 * @property string $name
 * @property string|null $description
 * @property ReportGroup $group
 * @property array<array-key, mixed>|null $config
 * @property bool $is_active
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 */
final class Report extends Model
{
    /** @use HasFactory<\Database\Factories\ReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'group' => ReportGroup::class,
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
