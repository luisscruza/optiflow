<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $original_filename
 * @property string $file_path
 * @property string $status
 * @property array<int, string>|null $headers
 * @property array<int, array<string, mixed>>|null $preview_rows
 * @property array<int, array<string, int|string>>|null $validation_errors
 * @property array<string, int>|null $summary
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $successful_rows
 * @property int $error_rows
 * @property \Carbon\CarbonImmutable|null $processed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read int $error_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductBulkUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductBulkUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductBulkUpdate query()
 *
 * @mixin \Eloquent
 */
final class ProductBulkUpdate extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $appends = ['error_count'];

    protected $fillable = [
        'original_filename',
        'file_path',
        'status',
        'headers',
        'preview_rows',
        'validation_errors',
        'summary',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'error_rows',
        'processed_at',
    ];

    /**
     * @return array<int, string>
     */
    public static function editableColumns(): array
    {
        return [
            'SKU',
            'NAME',
            'DESCRIPTION',
            'PRODUCT_TYPE',
            'PRICE',
            'COST',
            'TRACK_STOCK',
            'ALLOW_NEGATIVE_STOCK',
            'TAX_1_NAME',
            'TAX_1_RATE',
            'STATUS',
        ];
    }

    public static function workspaceStockColumnName(Workspace $workspace): string
    {
        $identifier = $workspace->code ?: $workspace->slug ?: $workspace->name;
        $identifier = Str::upper((string) preg_replace('/[^A-Za-z0-9]+/', '_', (string) $identifier));

        return 'WORKSPACE_'.$identifier.'_STOCK';
    }

    protected function errorCount(): Attribute
    {
        return Attribute::make(get: fn (): int => $this->error_rows);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'preview_rows' => 'array',
            'validation_errors' => 'array',
            'summary' => 'array',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
