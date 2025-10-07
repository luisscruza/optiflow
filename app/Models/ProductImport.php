<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $filename
 * @property string $original_filename
 * @property string $file_path
 * @property string $status
 * @property array<int, string>|null $headers
 * @property array<string, string>|null $column_mapping
 * @property array<int, array<string, mixed>>|null $import_data
 * @property array<string, mixed>|null $validation_errors
 * @property array<string, mixed>|null $import_summary
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $successful_rows
 * @property int $error_rows
 * @property \Carbon\CarbonImmutable|null $imported_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\ProductImportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereColumnMapping($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereErrorRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereImportData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereImportSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereImportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereSuccessfulRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImport whereValidationErrors($value)
 *
 * @mixin \Eloquent
 */
final class ProductImport extends Model
{
    /** @use HasFactory<\Database\Factories\ProductImportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_MAPPING = 'mapping';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'filename',
        'original_filename',
        'file_path',
        'status',
        'headers',
        'column_mapping',
        'import_data',
        'validation_errors',
        'import_summary',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'error_rows',
        'imported_at',
    ];

    /**
     * Get the available product fields that can be mapped.
     *
     * @return array<int, array<string, bool|string>>
     */
    public static function getAvailableFields(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nombre', 'required' => true],
            ['key' => 'sku', 'label' => 'SKU', 'required' => false],
            ['key' => 'description', 'label' => 'Descripción', 'required' => false],
            ['key' => 'price', 'label' => 'Precio', 'required' => false],
            ['key' => 'cost', 'label' => 'Costo', 'required' => false],
            ['key' => 'track_stock', 'label' => 'Rastrear inventario', 'required' => false],
            ['key' => 'allow_negative_stock', 'label' => 'Permitir inventario negativo', 'required' => false],
            ['key' => 'default_tax_rate', 'label' => 'Tasa de impuesto por defecto', 'required' => false],
            ['key' => 'category', 'label' => 'Categoría', 'required' => false],
        ];
    }

    /**
     * Get available stock fields that can be mapped per workspace.
     *
     * @return array<int, array<string, bool|string>>
     */
    public static function getStockFields(): array
    {
        return [
            ['key' => 'quantity', 'label' => 'Cantidad inicial', 'required' => false],
            ['key' => 'minimum_quantity', 'label' => 'Cantidad mínima', 'required' => false],
            ['key' => 'maximum_quantity', 'label' => 'Cantidad máxima', 'required' => false],
        ];
    }

    /**
     * Check if the import is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_MAPPING, self::STATUS_PROCESSING]);
    }

    /**
     * Check if the import is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the import has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark import as mapping stage.
     */
    public function markAsMapping(): bool
    {
        $this->status = self::STATUS_MAPPING;

        return $this->save();
    }

    /**
     * Mark import as processing.
     */
    public function markAsProcessing(): bool
    {
        $this->status = self::STATUS_PROCESSING;

        return $this->save();
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->imported_at = now();

        return $this->save();
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed(?array $errors = null): bool
    {
        $this->status = self::STATUS_FAILED;
        if ($errors !== null && $errors !== []) {
            $this->validation_errors = $errors;
        }

        return $this->save();
    }

    /**
     * Update the import progress.
     */
    public function updateProgress(int $processed, int $successful, int $errors): bool
    {
        $this->processed_rows = $processed;
        $this->successful_rows = $successful;
        $this->error_rows = $errors;

        return $this->save();
    }

    /**
     * Get the file from storage.
     */
    public function getFileContents(): ?string
    {
        if (! Storage::exists($this->file_path)) {
            return null;
        }

        return Storage::get($this->file_path);
    }

    /**
     * Delete the import file.
     */
    public function deleteFile(): bool
    {
        if (Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return true;
    }

    /**
     * Clean up import (delete file and record).
     */
    public function cleanup(): bool
    {
        $this->deleteFile();

        return (bool) $this->delete();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'column_mapping' => 'array',
            'import_data' => 'array',
            'validation_errors' => 'array',
            'import_summary' => 'array',
            'imported_at' => 'immutable_datetime',
        ];
    }
}
