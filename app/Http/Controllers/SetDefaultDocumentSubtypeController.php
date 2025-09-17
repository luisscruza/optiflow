<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SetDefaultDocumentSubtypeAction;
use App\Models\DocumentSubtype;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class SetDefaultDocumentSubtypeController extends Controller
{
    /**
     * Set a document subtype as default.
     */
    public function __invoke(DocumentSubtype $documentSubtype, SetDefaultDocumentSubtypeAction $action, User $user): RedirectResponse
    {
        $action->handle($user, $documentSubtype);

        return back()->with('success', 'Numeración establecida como preferida.');
    }
}
