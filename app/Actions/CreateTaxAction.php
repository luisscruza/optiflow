<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Tax;
use App\Models\User;

final class CreateTaxAction
{
    /**
     * Create a new tax.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Tax
    {
        // If marking this tax as default, unset all other defaults
        if ($data['is_default'] ?? false) {
            Tax::query()->where('is_default', true)->update(['is_default' => false]);
        }

        return Tax::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'rate' => $data['rate'],
            'is_default' => $data['is_default'] ?? false,
        ]);
    }
}
