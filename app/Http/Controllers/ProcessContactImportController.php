<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContactImportStatus;
use App\Http\Requests\ProcessContactImportRequest;
use App\Jobs\ProcessContactImportJob;
use App\Models\ContactImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class ProcessContactImportController
{
    /**
     * Process the import.
     */
    public function __invoke(ProcessContactImportRequest $request, ContactImport $contactImport): RedirectResponse
    {
        if ($contactImport->status !== ContactImportStatus::Mapping) {
            return redirect()->route('contact-imports.show', $contactImport->id)
                ->withErrors(['general' => 'Import is not ready for processing']);
        }

        if (! $contactImport->column_mapping) {
            return redirect()->route('contact-imports.show', $contactImport->id)
                ->withErrors(['general' => 'Column mapping is required before processing']);
        }

        $contactImport->markAsProcessing();

        dispatch(new ProcessContactImportJob(
            contactImportId: $contactImport->id,
            userId: Auth::id(),
        ));

        return redirect()->route('contact-imports.show', $contactImport->id)
            ->with('success', 'Importación en proceso. Verás los resultados al finalizar.');
    }
}
