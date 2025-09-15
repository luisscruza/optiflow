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
        // Check if tax is being used by products or document items
        if ($tax->products()->exists() || $tax->documentItems()->exists()) {
            throw new InvalidArgumentException('Cannot delete tax that is being used by products or documents.');
        }

        // Prevent deleting the default tax if it's the last one
        if ($tax->is_default && Tax::query()->count() <= 1) {
            throw new InvalidArgumentException('Cannot delete the only remaining tax.');
        }

        $tax->delete();
    }
}
