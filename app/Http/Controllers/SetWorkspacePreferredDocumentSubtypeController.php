<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SetWorkspacePreferredDocumentSubtypeAction;
use App\Models\DocumentSubtype;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

final class SetWorkspacePreferredDocumentSubtypeController extends Controller
{
    /**
     * Set a document subtype as preferred for a specific workspace.
     */
    public function __invoke(
        DocumentSubtype $documentSubtype,
        Workspace $workspace,
        SetWorkspacePreferredDocumentSubtypeAction $action
    ): RedirectResponse {
        $action->handle($workspace, $documentSubtype);

        return back()->with('success', "Numeración establecida como preferida para {$workspace->name}.");
    }
}
