<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tax;
use App\Models\User;

final class UpdateTaxAction
{
    /**
     * Update an existing tax.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, Tax $tax, array $data): Tax
    {
        // If marking this tax as default, unset all other defaults
        if (($data['is_default'] ?? false) && ! $tax->is_default) {
            Tax::query()->where('is_default', true)->update(['is_default' => false]);
        }

        $updateData = [
            'name' => $data['name'],
            'is_default' => $data['is_default'] ?? false,
        ];

        // Only update type and rate if the tax is not in use
        if (! $tax->isInUse()) {
            $updateData['type'] = $data['type'];
            $updateData['rate'] = $data['rate'];
        }

        $tax->update($updateData);

        return $tax->fresh();
    }
}
