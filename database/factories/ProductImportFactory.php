<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ProductImport>
 */
final class ProductImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'filename' => $this->faker->uuid().'.xlsx',
            'original_filename' => $this->faker->words(3, true).'.xlsx',
            'file_path' => 'imports/'.$this->faker->uuid().'.xlsx',
            'status' => ProductImport::STATUS_PENDING,
            'headers' => ['Name', 'SKU', 'Price', 'Cost'],
            'column_mapping' => null,
            'import_data' => null,
            'validation_errors' => null,
            'import_summary' => null,
            'total_rows' => $this->faker->numberBetween(10, 100),
            'processed_rows' => 0,
            'successful_rows' => 0,
            'error_rows' => 0,
            'imported_at' => null,
        ];
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductImport::STATUS_COMPLETED,
            'processed_rows' => $attributes['total_rows'],
            'successful_rows' => $attributes['total_rows'],
            'error_rows' => 0,
            'imported_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'import_summary' => [
                'imported' => $attributes['total_rows'],
                'skipped' => 0,
                'errors' => 0,
            ],
        ]);
    }

    /**
     * Indicate that the import has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductImport::STATUS_FAILED,
            'validation_errors' => [
                'general' => ['Import failed due to validation errors'],
                'row_errors' => [
                    1 => ['Name is required'],
                    2 => ['SKU already exists'],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the import is in mapping stage.
     */
    public function mapping(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductImport::STATUS_MAPPING,
            'headers' => ['Product Name', 'Product Code', 'Sell Price', 'Cost Price', 'Description'],
        ]);
    }
}
