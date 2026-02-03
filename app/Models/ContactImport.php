<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $filename
 * @property string $original_filename
 * @property string $file_path
 * @property string $status
 * @property array<int, array<string, mixed>>|null $source_files
 * @property array<int, string>|null $headers
 * @property array<string, string>|null $column_mapping
 * @property array<int, array<string, mixed>>|null $import_data
 * @property array<int, array<string, mixed>>|null $validation_errors
 * @property array<string, mixed>|null $import_summary
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $successful_rows
 * @property int $error_rows
 * @property \Carbon\CarbonImmutable|null $imported_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\ContactImportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereColumnMapping($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereErrorRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereHeaders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereImportData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereImportSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereImportedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereProcessedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereSuccessfulRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactImport whereValidationErrors($value)
 *
 * @mixin \Eloquent
 */
final class ContactImport extends Model
{
    /** @use HasFactory<\Database\Factories\ContactImportFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the available contact fields that can be mapped.
     *
     * @return array<int, array<string, bool|string>>
     */
    public static function getAvailableFields(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nombre', 'required' => true],
            ['key' => 'email', 'label' => 'Email', 'required' => false],
            ['key' => 'phone_primary', 'label' => 'Teléfono principal', 'required' => false],
            ['key' => 'phone_secondary', 'label' => 'Teléfono secundario', 'required' => false],
            ['key' => 'mobile', 'label' => 'Móvil', 'required' => false],
            ['key' => 'fax', 'label' => 'Fax', 'required' => false],
            ['key' => 'identification_type', 'label' => 'Tipo de identificación', 'required' => false],
            ['key' => 'identification_number', 'label' => 'Número de identificación', 'required' => false],
            ['key' => 'status', 'label' => 'Estado', 'required' => false],
            ['key' => 'observations', 'label' => 'Observaciones', 'required' => false],
            ['key' => 'credit_limit', 'label' => 'Límite de crédito', 'required' => false],
            ['key' => 'metadata', 'label' => 'Metadatos', 'required' => false],
            ['key' => 'birth_date', 'label' => 'Fecha de nacimiento', 'required' => false],
            ['key' => 'gender', 'label' => 'Género', 'required' => false],
            ['key' => 'created_at', 'label' => 'Creado en', 'required' => false],
            ['key' => 'updated_at', 'label' => 'Actualizado en', 'required' => false],
            ['key' => 'contact_type', 'label' => 'Tipo de contacto', 'required' => false],
            ['key' => 'id', 'label' => 'ID', 'required' => false],
        ];
    }

    /**
     * Check if the import is in progress.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [ContactImportStatus::Pending, ContactImportStatus::Mapping, ContactImportStatus::Processing], true);
    }

    /**
     * Check if the import is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ContactImportStatus::Completed;
    }

    /**
     * Check if the import has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === ContactImportStatus::Failed;
    }

    /**
     * Mark import as mapping stage.
     */
    public function markAsMapping(): bool
    {
        $this->status = ContactImportStatus::Mapping;

        return $this->save();
    }

    /**
     * Mark import as processing.
     */
    public function markAsProcessing(): bool
    {
        $this->status = ContactImportStatus::Processing;

        return $this->save();
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted(): bool
    {
        $this->status = ContactImportStatus::Completed;
        $this->imported_at = now();

        return $this->save();
    }

    /**
     * Mark import as failed.
     *
     * @param  array<int, array<string, mixed>>|null  $errors
     */
    public function markAsFailed(?array $errors = null): bool
    {
        $this->status = ContactImportStatus::Failed;
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
     * Get the file contents from storage.
     */
    public function getFileContents(): ?string
    {
        if (! Storage::exists($this->file_path)) {
            return null;
        }

        return Storage::get($this->file_path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_files' => 'array',
            'headers' => 'array',
            'column_mapping' => 'array',
            'import_data' => 'array',
            'validation_errors' => 'array',
            'import_summary' => 'array',
            'imported_at' => 'immutable_datetime',
            'status' => ContactImportStatus::class,
        ];
    }
}
