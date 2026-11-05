<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionAddendum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionAddendum>
 */
final class PrescriptionAddendumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'content' => fake()->sentence(),
        ];
    }
}
