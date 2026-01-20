<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProductImportAction;
use App\Actions\DeleteProductImportAction;
use App\Actions\UpdateProductImportMappingAction;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\MapColumnsRequest;
use App\Http\Requests\UploadProductImportRequest;
use App\Models\ProductImport;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ProductImportController extends Controller
{
    /**
     * Display a listing of recent imports.
     */
    public function index(): Response
    {
        $imports = ProductImport::query()
            ->latest()
            ->paginate(10);

        return Inertia::render('product-imports/index', [
            'imports' => $imports,
        ]);
    }

    /**
     * Show the form for creating a new import.
     */
    public function create(): Response
    {
        return Inertia::render('product-imports/create');
    }

    /**
     * Store a newly uploaded import file.
     */
    public function store(UploadProductImportRequest $request, CreateProductImportAction $action): RedirectResponse
    {
        try {
            $import = $action->handle($request->validated()['file']);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('product-imports.show', $import->id)
            ->with('success', 'File uploaded successfully. Please map the columns.');
    }

    /**
     * Display the import mapping interface.
     */
    public function show(ProductImport $productImport): Response
    {
        $import = $productImport;
        // Get available workspaces for the current user
        $user = Auth::user();
        $workspaces = Workspace::query()->whereHas('users', function ($query) use ($user): void {
            $query->where('user_id', $user->id);
        })->get();

        return Inertia::render('product-imports/show', [
            'import' => $import,
            'availableFields' => ProductImport::getAvailableFields(),
            'stockFields' => ProductImport::getStockFields(),
            'workspaces' => $workspaces,
            'previewData' => array_slice($import->import_data ?? [], 0, 5), // Show first 5 rows as preview
        ]);
    }

    /**
     * Update the column mapping.
     */
    public function update(MapColumnsRequest $request, ProductImport $productImport, UpdateProductImportMappingAction $action): RedirectResponse
    {
        try {
            $action->handle($productImport, $request->validated());
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('product-imports.show', $productImport->id)
            ->with('success', 'Column mapping saved successfully.');
    }

    /**
     * Remove the specified import.
     */
    public function destroy(ProductImport $productImport, DeleteProductImportAction $action): RedirectResponse
    {
        try {
            $action->handle($productImport);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('product-imports.index')
            ->with('success', 'Import deleted successfully.');
    }
}
