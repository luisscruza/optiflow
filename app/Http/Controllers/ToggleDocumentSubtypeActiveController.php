<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DocumentSubtype;
use Illuminate\Http\RedirectResponse;

final class ToggleDocumentSubtypeActiveController
{
    /**
     * Toggle the active state of a document subtype.
     */
    public function __invoke(DocumentSubtype $documentSubtype): RedirectResponse
    {
        $documentSubtype->update([
            'is_active' => ! $documentSubtype->is_active,
        ]);

        $message = $documentSubtype->is_active
            ? 'Numeración activada exitosamente.'
            : 'Numeración desactivada exitosamente.';

        return back()->with('success', $message);
    }
}
