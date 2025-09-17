<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentSubtype;
use App\Models\User;

final readonly class UpdateDocumentSubtypeAction
{
    /**
     * Update a document subtype with limited editable fields.
     *
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, DocumentSubtype $documentSubtype, array $validated): DocumentSubtype
    {
        // Only allow updating specific fields
        $allowedFields = [
            'name',
            'start_number',
            'end_number',
        ];

        $updateData = array_intersect_key($validated, array_flip($allowedFields));

        // If start_number is being updated and it's greater than current next_number,
        // update next_number to match start_number
        if (isset($updateData['start_number']) && $updateData['start_number'] > $documentSubtype->next_number) {
            $updateData['next_number'] = $updateData['start_number'];
        }

        $documentSubtype->update($updateData);

        return $documentSubtype->fresh();
    }
}
