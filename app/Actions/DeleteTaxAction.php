<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tax;
use App\Models\User;
use InvalidArgumentException;

final class DeleteTaxAction
{
    /**
     * Delete a tax if it's safe to do so.
     *
     * @throws InvalidArgumentException
     */
    public function handle(User $user, Tax $tax): void
    {
        if ($tax->products()->exists() || $tax->documentItems()->exists()) {
            throw new InvalidArgumentException('Cannot delete tax that is being used by products or documents.');
        }

        if ($tax->is_default && Tax::query()->count() <= 1) {
            throw new InvalidArgumentException('Cannot delete the only remaining tax.');
        }

        $tax->delete();
    }
}
