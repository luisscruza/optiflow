<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ClosePrescriptionAction
{
    public function __construct(private SetClinicalPinAction $setClinicalPin) {}

    public function handle(Prescription $prescription, User $user, string $pin, ?string $pinConfirmation = null): void
    {
        if ($prescription->isClosed()) {
            throw new ActionValidationException(['pin' => 'Esta historia clínica ya está cerrada.']);
        }

        $optometrist = $prescription->optometrist;

        if (! $optometrist) {
            throw new ActionValidationException(['pin' => 'Asigna un evaluador a la receta antes de cerrarla.']);
        }

        DB::transaction(function () use ($prescription, $user, $pin, $pinConfirmation, $optometrist): void {
            if (! $optometrist->hasClinicalPin()) {
                if ($pin !== $pinConfirmation) {
                    throw new ActionValidationException(['pin_confirmation' => 'Los PIN no coinciden.']);
                }

                $this->setClinicalPin->handle($optometrist, $user, $pin);
            } elseif (! $optometrist->matchesClinicalPin($pin)) {
                throw new ActionValidationException(['pin' => "PIN incorrecto. Solo {$optometrist->name} puede cerrar esta historia."]);
            }

            $prescription->closure()->create([
                'optometrist_id' => $optometrist->id,
                'user_id' => $user->id,
                'closed_at' => now(),
            ]);

            activity()
                ->performedOn($prescription)
                ->causedBy($user)
                ->withProperties(['optometrist_id' => $optometrist->id])
                ->log("Cerró la historia clínica con el PIN de {$optometrist->name}");
        });
    }
}
