<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInvoiceAction;
use App\Actions\UpdateInvoiceAction;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Enums\QuotationStatus;
use App\Enums\TaxType;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\BankAccount;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Quotation;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
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
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesView), 403);

        $query = Invoice::query()
            ->with(['contact', 'documentSubtype'])
            ->orderBy('issue_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search): void {
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
            'bankAccounts' => Inertia::optional(fn() => BankAccount::onlyActive()->with('currency')->orderBy('name')->get()),
            'paymentMethods' => Inertia::optional(fn(): array => PaymentMethod::options()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesCreate), 403);

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $customers = Contact::query()
            ->orderBy('name')
            ->get()
            ->map(function ($contact) {
                $phone = $contact->phone_primary ?? null;
                $contact->name = "{$contact->name}" . ($phone ? " ({$phone})" : '');

                return $contact;
            });

        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace): void {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace): void {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace): Product {
                $stock = $currentWorkspace ? $product->stocks->first() : null;
                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                unset($product->stocks);

                return $product;
            });

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::query()->findOrFail($request->get('document_subtype_id'))
            : DocumentSubtype::active()->where('is_default', true)->first();

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        // Group taxes by type for the multi-select component
        $taxesGroupedByType = Tax::query()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn($taxes, $type): array => [
                $type => [
                    'label' => TaxType::tryFrom($type)?->label() ?? $type,
                    'isExclusive' => TaxType::tryFrom($type)?->isExclusive() ?? false,
                    'taxes' => $taxes->toArray(),
                ],
            ])
            ->toArray();

        return Inertia::render('invoices/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
            'defaultNote' => CompanyDetail::getByKey('terms_conditions'),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
            'taxesGroupedByType' => $taxesGroupedByType,
        ]);
    }

    /**
     * Store a newly created invoice.
     *
     * @throws Throwable
     */
    public function store(Request $request, #[CurrentUser] User $user, CreateInvoiceAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesCreate), 403);

        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $request->all());

        if ($result->isError()) {
            Session::flash('error', $result->error);

            return redirect()->route('invoices.create')
                ->withErrors(['error' => $result->error]);
        }

        if ($request->filled('quotation_id')) {
            Quotation::query()
                ->where('id', $request->integer('quotation_id'))
                ->update(['status' => QuotationStatus::Converted]);
        }

        return redirect()->route('invoices.index')->with('success', 'Factura creada exitosamente.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesView), 403);

        $invoice->load([
            'contact',
            'documentSubtype',
            'items.product',
            'items.taxes',
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
    public function edit(Request $request, Invoice $invoice, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesEdit), 403);

        $currentWorkspace = Context::get('workspace');

        $invoice->load(['contact', 'documentSubtype', 'items.product', 'items.taxes']);

        $documentSubtypes = DocumentSubtype::active()
            ->forInvoice()
            ->orderBy('name')
            ->get();

        $customers = Contact::customers()->orderBy('name')->get();

        $products = Product::with(['defaultTax'])
            ->when($currentWorkspace, function ($query) use ($currentWorkspace): void {
                $query->with(['stocks' => function ($stockQuery) use ($currentWorkspace): void {
                    $stockQuery->where('workspace_id', $currentWorkspace->id);
                }]);
            })
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($currentWorkspace): Product {
                $stock = $currentWorkspace ? $product->stocks->first() : null;

                $product->current_stock = $stock;
                $product->stock_quantity = $stock ? $stock->quantity : 0;
                $product->minimum_quantity = $stock ? $stock->minimum_quantity : 0;
                $product->stock_status = $this->getStockStatus($product, $stock);

                unset($product->stocks);

                return $product;
            });

        $taxes = Tax::query()->orderBy('name')->get();

        // Get the NCF - only generate a new one if the document_subtype_id changed
        $ncf = $invoice->document_number;
        if ($request->filled('document_subtype_id') && (int) $request->get('document_subtype_id') !== $invoice->document_subtype_id) {
            $documentSubtype = DocumentSubtype::query()->findOrFail($request->get('document_subtype_id'));
            $ncf = $documentSubtype->generateNCF();
        }

        // Group taxes by type for the multi-select component
        $taxesGroupedByType = Tax::query()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn($taxes, $type): array => [
                $type => [
                    'label' => TaxType::tryFrom($type)?->label() ?? $type,
                    'isExclusive' => TaxType::tryFrom($type)?->isExclusive() ?? false,
                    'taxes' => $taxes->toArray(),
                ],
            ])
            ->toArray();

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'taxes' => $taxes,
            'taxesGroupedByType' => $taxesGroupedByType,
            'ncf' => $ncf,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice, UpdateInvoiceAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesEdit), 403);

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
    public function destroy(Invoice $invoice, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesDelete), 403);

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

        if (! $stock instanceof ProductStock || $stock->quantity <= 0) {
            return 'out_of_stock';
        }

        if ($stock->quantity <= $stock->minimum_quantity) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
