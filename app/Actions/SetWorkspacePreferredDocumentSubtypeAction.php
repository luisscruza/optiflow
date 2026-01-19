<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DocumentSubtype;
use App\Models\Workspace;

final class SetWorkspacePreferredDocumentSubtypeAction
{
    /**
     * Set a document subtype as preferred for a specific workspace.
     */
    public function handle(Workspace $workspace, DocumentSubtype $documentSubtype): DocumentSubtype
    {
        $workspace->documentSubtypes()
            ->wherePivot('is_preferred', true)
            ->each(function (DocumentSubtype $subtype) use ($workspace): void {
                $workspace->documentSubtypes()->updateExistingPivot($subtype->id, ['is_preferred' => false]);
            });

        $workspace->documentSubtypes()->syncWithoutDetaching([
            $documentSubtype->id => ['is_preferred' => true],
        ]);

        return $documentSubtype->fresh();
    }
}
