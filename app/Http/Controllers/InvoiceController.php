<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Document;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('invoices/index', [
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
        $documentSubtypes = DocumentSubtype::active()
            ->orderBy('name')
            ->get();

        $customers = Contact::customers()
            ->orderBy('name')
            ->get();

        $products = Product::orderBy('name')->get();

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::find($request->get('document_subtype_id'))
            : DocumentSubtype::active()->where('is_default', true)->first();

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype?->id,
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
}
