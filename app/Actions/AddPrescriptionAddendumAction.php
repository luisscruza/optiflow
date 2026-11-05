<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\Prescription;
use App\Models\PrescriptionAddendum;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class AddPrescriptionAddendumAction
{
    public function handle(Prescription $prescription, User $user, string $content, string $pin): PrescriptionAddendum
    {
        if (! $prescription->isClosed()) {
            throw new ActionValidationException(['content' => 'La historia clínica aún está abierta; corrígela editándola.']);
        }

        $optometrist = $prescription->closure?->optometrist ?? $prescription->optometrist;

        if (! $optometrist?->matchesClinicalPin($pin)) {
            throw new ActionValidationException(['pin' => 'PIN incorrecto. La adenda debe firmarla '.($optometrist->name ?? 'el evaluador').'.']);
        }

        return DB::transaction(function () use ($prescription, $user, $content, $optometrist): PrescriptionAddendum {
            $addendum = $prescription->addenda()->create([
                'content' => $content,
                'optometrist_id' => $optometrist->id,
                'user_id' => $user->id,
            ]);

            activity()
                ->performedOn($prescription)
                ->causedBy($user)
                ->withProperties(['addendum_id' => $addendum->id, 'optometrist_id' => $optometrist->id])
                ->log("Agregó una adenda firmada por {$optometrist->name}");

            return $addendum;
        });
    }
}
