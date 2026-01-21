<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prescription>
 */
final class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'patient_id' => Contact::factory(),
            'created_by' => User::factory(),
            'optometrist_id' => Contact::factory(),
            'motivos_consulta_otros' => null,
            'estado_salud_actual_otros' => null,
            'historia_ocular_familiar_otros' => null,
            'proxima_cita' => null,
        ];
    }
}
