<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentSubtype;
use App\Models\User;

final class CreateDocumentSubtypeAction
{
    /**
     * Create a new document subtype (NCF configuration).
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): DocumentSubtype
    {
        if ($validated['is_default'] ?? false) {
            DocumentSubtype::query()->update(['is_default' => false]);
        }

        $validated['next_number'] = $validated['start_number'];

        return DocumentSubtype::query()->create($validated);
    }
}
