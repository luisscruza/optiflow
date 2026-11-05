<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;

final readonly class ResetClinicalPinAction
{
    public function handle(Contact $optometrist, User $user): void
    {
        $optometrist->forceFill(['clinical_pin' => null])->save();

        activity()
            ->performedOn($optometrist)
            ->causedBy($user)
            ->log('Restableció el PIN clínico');
    }
}
