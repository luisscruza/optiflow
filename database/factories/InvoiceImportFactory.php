<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceImportStatus;
use App\Models\InvoiceImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceImport>
 */
final class InvoiceImportFactory extends Factory
{
    protected $model = InvoiceImport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.csv';

        return [
            'user_id' => User::factory(),
            'filename' => $filename,
            'original_filename' => 'invoices.csv',
            'file_path' => 'imports/invoices/'.$filename,
            'limit' => 50,
            'offset' => 0,
            'total_records' => 0,
            'processed_records' => 0,
            'imported_records' => 0,
            'skipped_records' => 0,
            'error_records' => 0,
            'status' => InvoiceImportStatus::Pending,
            'exit_code' => null,
            'output' => null,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
