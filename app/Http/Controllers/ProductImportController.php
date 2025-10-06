<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ParseExcelFileAction;
use App\Actions\ProcessProductImportAction;
use App\Http\Requests\MapColumnsRequest;
use App\Http\Requests\ProcessImportRequest;
use App\Http\Requests\UploadProductImportRequest;
use App\Models\ProductImport;
use App\Models\Workspace;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

final class ProductImportController extends Controller
{
    public function __construct(
        private readonly ParseExcelFileAction $parseExcelFileAction,
        private readonly ProcessProductImportAction $processProductImportAction
    ) {}

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
            $import = ProductImport::create([
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
        $workspaces = Workspace::whereHas('users', function ($query) use ($user): void {
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
     * Process the import.
     */
    public function process(ProcessImportRequest $request, ProductImport $productImport): RedirectResponse
    {
        if ($productImport->status !== ProductImport::STATUS_MAPPING) {
            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Import is not ready for processing']);
        }

        if (! $productImport->column_mapping) {
            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Column mapping is required before processing']);
        }

        try {
            $user = Auth::user();
            $validatedData = $request->validated();
            $workspaceIds = $validatedData['workspaces'];
            $stockMapping = $validatedData['stock_mapping'] ?? [];

            $workspaces = Workspace::whereIn('id', $workspaceIds)->get();

            // Process the import in the background (or synchronously for now)
            $result = $this->processProductImportAction->handle($productImport, $workspaces, $stockMapping);

            if ($result['errors'] === 0) {
                return redirect()->route('product-imports.show', $productImport->id)
                    ->with('success', "Import completed successfully! {$result['imported']} products imported.");
            }

            return redirect()->route('product-imports.show', $productImport->id)
                ->with('warning', "Import completed with {$result['errors']} errors. {$result['imported']} products imported successfully.");

        } catch (Exception $e) {
            $productImport->markAsFailed(['general' => $e->getMessage()]);

            return redirect()->route('product-imports.show', $productImport->id)
                ->withErrors(['general' => 'Import failed: '.$e->getMessage()]);
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

    /**
     * Download the import template.
     */
    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $writer = new Writer();
        $tempFile = tempnam(sys_get_temp_dir(), 'product_import_template_');

        try {
            $writer->openToFile($tempFile);

            // Create header row using Row::fromValues()
            $headerRow = Row::fromValues([
                'Nombre',
                'SKU',
                'Descripción',
                'Precio',
                'Costo',
                'Controlar Stock',
                'Permitir Stock Negativo',
                'ID Impuesto por Defecto',
                'Categoría',
            ]);
            $writer->addRow($headerRow);

            // Add sample data row
            $sampleRow = Row::fromValues([
                'Producto de Ejemplo',
                'SKU-001',
                'Descripción del producto de ejemplo',
                '100.00',
                '60.00',
                'Sí',
                'No',
                '',
                'Categoría Ejemplo',
            ]);
            $writer->addRow($sampleRow);

            $writer->close();

            return response()->download($tempFile, 'plantilla-importacion-productos.xlsx')->deleteFileAfterSend(true);

        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            abort(500, 'Error generating template: '.$e->getMessage());
        }
    }
}
