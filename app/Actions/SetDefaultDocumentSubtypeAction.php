<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentSubtype;
use App\Models\User;

final class SetDefaultDocumentSubtypeAction
{
    /**
     * Set a document subtype as the default one.
     */
    public function handle(User $user, DocumentSubtype $documentSubtype): DocumentSubtype
    {
        DocumentSubtype::query()->update(['is_default' => false]);

        $documentSubtype->update(['is_default' => true]);

        return $documentSubtype->fresh();
    }
}
