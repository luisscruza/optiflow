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
        if (! $workspace->documentSubtypes()->whereKey($documentSubtype->id)->exists()) {
            $workspace->documentSubtypes()->syncWithoutDetaching([
                $documentSubtype->id => ['is_preferred' => false],
            ]);
        }

        $workspace->documentSubtypes()
            ->where('document_subtypes.type', $documentSubtype->type)
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
