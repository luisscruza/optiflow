<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateContactImportAction;
use App\Actions\DeleteContactImportAction;
use App\Actions\UpdateContactImportMappingAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateContactImportRequest;
use App\Http\Requests\UpdateContactImportRequest;
use App\Models\ContactImport;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContactImportController
{
    /**
     * Display a listing of recent imports.
     */
    public function index(): Response
    {
        $imports = ContactImport::query()
            ->latest()
            ->paginate(10);

        return Inertia::render('contact-imports/index', [
            'imports' => $imports,
        ]);
    }

    /**
     * Show the form for creating a new import.
     */
    public function create(): Response
    {
        return Inertia::render('contact-imports/create');
    }

    /**
     * Store a newly uploaded import file.
     */
    public function store(CreateContactImportRequest $request, CreateContactImportAction $action): RedirectResponse
    {
        try {
            $validated = $request->validated();

            if (isset($validated['files'])) {
                $import = $action->handleMany($validated['files']);

                return redirect()->route('contact-imports.show', $import->id)
                    ->with('success', 'Archivos cargados correctamente. Mapea las columnas para toda la importación.');
            }

            $import = $action->handle($validated['file']);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('contact-imports.show', $import->id)
            ->with('success', 'Archivo cargado correctamente. Mapea las columnas.');
    }

    /**
     * Display the import mapping interface.
     */
    public function show(ContactImport $contactImport): Response
    {
        return Inertia::render('contact-imports/show', [
            'import' => $contactImport,
            'availableFields' => ContactImport::getAvailableFields(),
            'previewData' => array_slice($contactImport->import_data ?? [], 0, 5),
        ]);
    }

    /**
     * Update the column mapping.
     */
    public function update(UpdateContactImportRequest $request, ContactImport $contactImport, UpdateContactImportMappingAction $action): RedirectResponse
    {
        try {
            $action->handle($contactImport, $request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('contact-imports.show', $contactImport->id)
            ->with('success', 'Mapeo guardado correctamente.');
    }

    /**
     * Remove the specified import.
     */
    public function destroy(ContactImport $contactImport, DeleteContactImportAction $action): RedirectResponse
    {
        try {
            $action->handle($contactImport);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('contact-imports.index')
            ->with('success', 'Importación eliminada correctamente.');
    }
}
