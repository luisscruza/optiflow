<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Document::query()
            ->where('type', 'invoice')
            ->with(['contact', 'documentSubtype'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->paginate(15)->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        // Handle workspace switching if requested
        if ($request->filled('workspace_id')) {
            $workspaceId = $request->get('workspace_id');
            $workspace = Workspace::findOrFail($workspaceId);

            // Verify user has access to this workspace
            if (! Auth::user()->hasAccessToWorkspace($workspace)) {
                abort(403, 'No tienes acceso a este espacio de trabajo.');
            }

            // Switch the user's current workspace
            Auth::user()->switchToWorkspace($workspace);
        }

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::active()
            ->orderBy('name')
            ->get();

        $customers = Contact::customers()
            ->when($currentWorkspace, fn ($query) => $query->where('workspace_id', $currentWorkspace->id))
            ->orderBy('name')
            ->get();

        // Get products with stock information for the current workspace
        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace) {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace) {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace) {
                // Add stock information to each product
                $stock = $currentWorkspace ? $product->stocks->first() : null;
                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                // Remove the stocks collection to keep response clean
                unset($product->stocks);

                return $product;
            });

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::find($request->get('document_subtype_id'))
            : DocumentSubtype::active()->where('is_default', true)->first();

        // Get available workspaces for the user
        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype?->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        // This will be implemented with CreateInvoiceAction
        return redirect()->route('invoices.index');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Document $invoice): Response
    {
        $invoice->load(['contact', 'documentSubtype', 'items.product', 'items.tax']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Document $invoice): Response
    {
        $invoice->load(['contact', 'documentSubtype', 'items.product', 'items.tax']);

        $documentSubtypes = DocumentSubtype::orderBy('name')->get();
        $customers = Contact::customers()->orderBy('name')->get();
        $products = Product::with('defaultTax')->orderBy('name')->get();
        $taxes = Tax::orderBy('name')->get();

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'taxes' => $taxes,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Document $invoice, User $user): RedirectResponse
    {
        // This will be implemented with UpdateInvoiceAction
        return redirect()->route('invoices.show', $invoice);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Document $invoice): RedirectResponse
    {
        // For now, we'll only allow deleting draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Solo se pueden eliminar facturas en borrador.']);
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Factura eliminada exitosamente.');
    }

    /**
     * Get stock status for a product.
     */
    private function getStockStatus(Product $product, ?ProductStock $stock): string
    {
        if (! $product->track_stock) {
            return 'not_tracked';
        }

        if (! $stock || $stock->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($stock->quantity <= $stock->minimum_quantity) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
