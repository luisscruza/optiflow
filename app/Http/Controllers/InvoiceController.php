<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInvoiceAction;
use App\Actions\UpdateInvoiceAction;
use App\Enums\PaymentMethod;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::query()
            ->with(['contact', 'documentSubtype'])
            ->orderBy('issue_date', 'desc')
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

        $invoices = $query->paginate(30)->withQueryString();

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
            ],
            'bankAccounts' => Inertia::optional(fn () => BankAccount::onlyActive()->with('currency')->orderBy('name')->get()),
            'paymentMethods' => Inertia::optional(fn () => PaymentMethod::options()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $customers = Contact::customers()
            ->when($currentWorkspace, fn ($query) => $query->where('workspace_id', $currentWorkspace->id))
            ->orderBy('name')
            ->get();

        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace) {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace) {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace) {
                $stock = $currentWorkspace ? $product->stocks->first() : null;
                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                unset($product->stocks);

                return $product;
            });

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::findOrFail($request->get('document_subtype_id'))
            : DocumentSubtype::active()->where('is_default', true)->first();

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
        ]);
    }

    /**
     * Store a newly created invoice.
     *
     * @throws Throwable
     */
    public function store(Request $request, User $user, CreateInvoiceAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $request->all());

        if ($result->isError()) {
            Session::flash('error', $result->error);

            return redirect()->route('invoices.create')
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('invoices.index')->with('success', 'Factura creada exitosamente.');

    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.tax',
            'payments.bankAccount',
            'payments.currency',
            'comments.commentator',
            'comments.comments.commentator', 
            'comments.comments.comments.commentator', 
        ]);

        // Get bank accounts and payment methods for payment registration
        $bankAccounts = BankAccount::onlyActive()->with('currency')->get();
        $paymentMethods = [
            'cash' => 'Efectivo',
            'transfer' => 'Transferencia',
            'check' => 'Cheque',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta de Débito',
            'other' => 'Otro',
        ];

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'bankAccounts' => $bankAccounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): Response
    {
        $currentWorkspace = Context::get('workspace');

        $invoice->load(['contact', 'documentSubtype', 'items.product', 'items.tax']);

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $customers = Contact::customers()->orderBy('name')->get();

        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace) {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace) {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace) {
                $stock = $currentWorkspace ? $product->stocks->first() : null;

                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                unset($product->stocks);

                return $product;
            });

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
    public function update(UpdateInvoiceRequest $request, Invoice $invoice, UpdateInvoiceAction $action): RedirectResponse
    {
        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $invoice, $request->validated());

        if ($result->isError()) {
            Session::flash('error', $result->error);

            return redirect()->route('invoices.edit', $invoice)
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Factura actualizada exitosamente.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
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

    /**
     * Clean UTF-8 characters from a string.
     */
    private function cleanUtf8String(string $str): string
    {
        // Remove null bytes and other control characters
        $str = str_replace(["\0", "\x0B"], '', $str);

        // Convert to UTF-8 if it's not already
        if (! mb_check_encoding($str, 'UTF-8')) {
            // Try to detect encoding and convert
            $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encoding);
            } else {
                // If we can't detect encoding, remove invalid UTF-8 sequences
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
            }
        }

        // Remove any remaining invalid UTF-8 characters
        $str = filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        // Trim whitespace
        return trim($str ?? '');
    }
}
