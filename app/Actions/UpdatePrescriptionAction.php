<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePrescriptionAction
{
    /**
     * Execute the action.
     */
    public function handle(Prescription $prescription, User $user, array $data): void
    {
        DB::transaction(function () use ($prescription, $data): void {
            $prescriptionData = collect($data)
                ->except([
                    'motivos_consulta',
                    'estado_salud_actual',
                    'historia_ocular_familiar',
                    'contact_id',
                ])
                ->merge([
                    'patient_id' => $data['contact_id'],
                ])
                ->toArray();

            $prescription->update($prescriptionData);

            // Handle many-to-many relationships if they exist in the data
            if (isset($data['motivos_consulta'])) {
                $this->syncItems($prescription, $data['motivos_consulta'], 'motivos_consulta');
            }

            if (isset($data['estado_salud_actual'])) {
                $this->syncItems($prescription, $data['estado_salud_actual'], 'estado_salud_actual');
            }

            if (isset($data['historia_ocular_familiar'])) {
                $this->syncItems($prescription, $data['historia_ocular_familiar'], 'historia_ocular_familiar');
            }
        });
    }

    /**
     * Sync mastertable items with the prescription.
     *
     * @param  array<int>  $itemIds
     */
    private function syncItems(Prescription $prescription, array $itemIds, string $alias): void
    {
        // Remove existing items for this alias
        $prescription->belongsToMany(
            related: \App\Models\MastertableItem::class,
            table: 'prescription_item',
            foreignPivotKey: 'prescription_id',
            relatedPivotKey: 'mastertable_item_id'
        )
            ->wherePivot('mastertable_alias', $alias)
            ->detach();

        // Attach new items
        $attachData = collect($itemIds)->mapWithKeys(function ($itemId) use ($alias): array {
            return [$itemId => ['mastertable_alias' => $alias]];
        })->all();

        $prescription->belongsToMany(
            related: \App\Models\MastertableItem::class,
            table: 'prescription_item',
            foreignPivotKey: 'prescription_id',
            relatedPivotKey: 'mastertable_item_id'
        )->attach($attachData);
    }
}
