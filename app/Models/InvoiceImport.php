<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $filename
 * @property string $original_filename
 * @property string $file_path
 * @property int $limit
 * @property int $offset
 * @property int $total_records
 * @property int $processed_records
 * @property int $imported_records
 * @property int $skipped_records
 * @property int $error_records
 * @property string $status
 * @property int|null $exit_code
 * @property string|null $output
 * @property string|null $error_message
 * @property \Carbon\CarbonImmutable|null $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 *
 * @method static \Database\Factories\InvoiceImportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereExitCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereTotalRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereProcessedRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereImportedRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereSkippedRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereErrorRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvoiceImport whereUserId($value)
 *
 * @mixin \Eloquent
 */
final class InvoiceImport extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceImportFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    public function isInProgress(): bool
    {
        return in_array($this->status, [InvoiceImportStatus::Pending, InvoiceImportStatus::Processing], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === InvoiceImportStatus::Completed;
    }

    public function hasFailed(): bool
    {
        return $this->status === InvoiceImportStatus::Failed;
    }

    public function markAsProcessing(): bool
    {
        $this->status = InvoiceImportStatus::Processing;
        $this->started_at = now();

        return $this->save();
    }

    public function markAsCompleted(int $exitCode, ?string $output = null): bool
    {
        $this->status = InvoiceImportStatus::Completed;
        $this->exit_code = $exitCode;
        $this->output = $output;
        $this->finished_at = now();

        return $this->save();
    }

    public function markAsFailed(?string $message = null, ?string $output = null): bool
    {
        $this->status = InvoiceImportStatus::Failed;
        $this->error_message = $message;
        $this->output = $output;
        $this->finished_at = now();

        return $this->save();
    }

    public function updateProgress(int $processed, int $imported, int $skipped, int $errors, ?int $total = null): bool
    {
        $this->processed_records = $processed;
        $this->imported_records = $imported;
        $this->skipped_records = $skipped;
        $this->error_records = $errors;

        if ($total !== null) {
            $this->total_records = $total;
        }

        return $this->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceImportStatus::class,
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}
