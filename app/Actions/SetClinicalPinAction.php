<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ContactType;
use App\Exceptions\ActionValidationException;
use App\Models\Contact;
use App\Models\User;

final readonly class SetClinicalPinAction
{
    public function handle(Contact $optometrist, User $user, string $pin, ?string $currentPin = null): void
    {
        if ($optometrist->contact_type !== ContactType::Optometrist) {
            throw new ActionValidationException(['pin' => 'Solo los evaluadores pueden tener PIN clínico.']);
        }

        $isChange = $optometrist->hasClinicalPin();

        if ($isChange && ! $optometrist->matchesClinicalPin((string) $currentPin)) {
            throw new ActionValidationException(['current_pin' => 'El PIN actual no es correcto.']);
        }

        $optometrist->forceFill(['clinical_pin' => $pin])->save();

        activity()
            ->performedOn($optometrist)
            ->causedBy($user)
            ->log($isChange ? 'Cambió el PIN clínico' : 'Configuró el PIN clínico');
    }
}
