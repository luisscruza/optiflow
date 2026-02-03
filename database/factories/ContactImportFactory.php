<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContactImportStatus;
use App\Models\ContactImport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactImport>
 */
final class ContactImportFactory extends Factory
{
    protected $model = ContactImport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.csv';

        return [
            'filename' => $filename,
            'original_filename' => 'contacts.csv',
            'file_path' => 'imports/'.$filename,
            'source_files' => [
                [
                    'filename' => $filename,
                    'original_filename' => 'contacts.csv',
                    'file_path' => 'imports/'.$filename,
                    'has_header' => true,
                ],
            ],
            'status' => ContactImportStatus::Mapping,
            'headers' => ['name', 'email'],
            'column_mapping' => ['name' => 'name', 'email' => 'email'],
            'import_data' => [
                ['name' => 'Maria Perez', 'email' => 'maria@example.com'],
            ],
            'validation_errors' => [],
            'import_summary' => null,
            'total_rows' => 1,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'error_rows' => 0,
            'imported_at' => null,
        ];
    }
}
