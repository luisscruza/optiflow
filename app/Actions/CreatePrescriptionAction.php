<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreatePrescriptionAction
{
    /**
     * Execute the action.
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {

            $prescriptionData = collect($data)
                ->except([
                    'motivos_consulta',
                    'estado_salud_actual',
                    'historia_ocular_familiar',
                    'contact_id',
                ])
                ->merge([
                    'patient_id' => $data['contact_id'],
                    'created_by' => $user->id,
                ])
                ->toArray();

            Prescription::create($prescriptionData);
        });
    }
}
