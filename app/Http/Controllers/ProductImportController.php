<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ParseExcelFileAction;
use App\Http\Requests\MapColumnsRequest;
use App\Http\Requests\UploadProductImportRequest;
use App\Models\ProductImport;
use App\Models\Workspace;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ProductImportController extends Controller
{
    public function __construct(private readonly ParseExcelFileAction $parseExcelFileAction) {}

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
    public function store(UploadProductImportRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        /** @var UploadedFile $file */
        $file = $validatedData['file'];

        try {
            // Store the file
            $filename = uniqid().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs('imports', $filename);

            // Create the import record
            $import = ProductImport::query()->create([
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'status' => ProductImport::STATUS_PENDING,
            ]);

            // Parse the file to extract headers and sample data
            $parseResult = $this->parseExcelFileAction->handle($import); // Limit to 50 rows for preview

            $import->update([
                'headers' => $parseResult['headers'],
                'import_data' => $parseResult['data'],
                'total_rows' => $parseResult['total_rows'],
                'status' => ProductImport::STATUS_MAPPING,
            ]);

            return redirect()->route('product-imports.show', $import->id)
                ->with('success', 'File uploaded successfully. Please map the columns.');

        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'file' => 'Failed to process file: '.$e->getMessage(),
            ]);
        }
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
    public function update(MapColumnsRequest $request, ProductImport $productImport): RedirectResponse
    {
        if ($productImport->status !== ProductImport::STATUS_MAPPING) {
            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Import is not in mapping stage']);
        }

        try {
            $productImport->update([
                'column_mapping' => $request->validated()['column_mapping'],
            ]);

            return redirect()->route('product-imports.show', $productImport->id)
                ->with('success', 'Column mapping saved successfully.');

        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'general' => 'Failed to save mapping: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified import.
     */
    public function destroy(ProductImport $productImport): RedirectResponse
    {
        try {
            $productImport->cleanup();

            return redirect()->route('product-imports.index')
                ->with('success', 'Import deleted successfully.');

        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'general' => 'Failed to delete import: '.$e->getMessage(),
            ]);
        }
    }

}
