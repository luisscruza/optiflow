<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateInvoiceAction;
use App\Actions\DeleteInvoiceAction;
use App\Actions\UpdateInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\Permission;
use App\Enums\TaxType;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\BankAccount;
use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Salesman;
use App\Models\Tax;
use App\Models\User;
use App\Models\Workspace;
use App\Tables\InvoicesTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class InvoiceController
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::InvoicesView), 403);

        return Inertia::render('invoices/index', [
            'invoices' => InvoicesTable::make($request),
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
                $product->setAttribute('current_stock', $stock);
                $product->setAttribute('stock_quantity', $stock ? $stock->quantity : 0);
                $product->setAttribute('minimum_quantity', $stock ? $stock->minimum_quantity : 0);
                $product->setAttribute('stock_status', $this->getStockStatus($product, $stock));

                unset($product->stocks);

                return $product;
            });

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::query()->findOrFail($request->get('document_subtype_id'))
            : $this->getDefaultDocumentSubtype($currentWorkspace);

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

        $salesmen = Salesman::query()
            ->orderBy('name')
            ->orderBy('surname')
            ->get()
            ->map(fn($salesman) => [
                'id' => $salesman->id,
                'name' => $salesman->name,
                'surname' => $salesman->surname,
                'full_name' => $salesman->full_name,
            ]);

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
            'salesmen' => $salesmen,
        ]);
    }

    /**
     * Store a newly created invoice.
     *
     * @throws Throwable
     */
    public function store(CreateInvoiceRequest $request, #[CurrentUser] User $user, CreateInvoiceAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesCreate), 403);

        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $request->validated());

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
            'comments',
            'salesmen',
        ]);

        // Get activity logs for the invoice, its items, and related payments (using morph map values)
        $activities = \Spatie\Activitylog\Models\Activity::query()
            ->where(function ($query) use ($invoice) {
                $query->where(function ($q) use ($invoice) {
                    $q->where('subject_type', 'invoice')
                        ->where('subject_id', $invoice->id);
                })
                    ->orWhere(function ($q) use ($invoice) {
                        $q->where('subject_type', 'invoice_item')
                            ->whereIn('subject_id', $invoice->items->pluck('id'));
                    })
                    ->orWhere(function ($q) use ($invoice) {
                        $q->where('subject_type', 'payment')
                            ->whereIn('subject_id', $invoice->payments->pluck('id'));
                    });
            })
            ->with('causer')
            ->orderBy('created_at')
            ->get();

        // Collect field labels from all auditable models
        $fieldLabels = collect([
            $invoice->getActivityFieldLabels(),
            ...$invoice->items->map(fn($item) => $item->getActivityFieldLabels()),
            ...$invoice->payments->map(fn($payment) => $payment->getActivityFieldLabels()),
        ])->reduce(fn($carry, $labels) => array_merge($carry, $labels), []);

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
            'activities' => $activities,
            'activityFieldLabels' => $fieldLabels,
            'bankAccounts' => $bankAccounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Request $request, Invoice $invoice, #[CurrentUser] User $user): Response|RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesEdit), 403);

        if ($invoice->status !== InvoiceStatus::PendingPayment) {
            return redirect()->back()->with('error', 'Esta factura tiene un pago registrado. Para editarla, primero elimina los pagos asociados.');
        }

        $currentWorkspace = Context::get('workspace');

        $invoice->load(['contact', 'documentSubtype', 'items.product', 'items.taxes', 'salesmen']);

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

                $product->setAttribute('current_stock', $stock);
                $product->setAttribute('stock_quantity', $stock ? $stock->quantity : 0);
                $product->setAttribute('minimum_quantity', $stock ? $stock->minimum_quantity : 0);
                $product->setAttribute('stock_status', $this->getStockStatus($product, $stock));

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

        $salesmen = Salesman::query()
            ->orderBy('name')
            ->orderBy('surname')
            ->get()
            ->map(fn($salesman) => [
                'id' => $salesman->id,
                'name' => $salesman->name,
                'surname' => $salesman->surname,
                'full_name' => $salesman->full_name,
            ]);

        return Inertia::render('invoices/Edit', [
            'invoice' => $invoice,
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'taxes' => $taxes,
            'taxesGroupedByType' => $taxesGroupedByType,
            'ncf' => $ncf,
            'salesmen' => $salesmen,
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
    public function destroy(Invoice $invoice, #[CurrentUser] User $user, DeleteInvoiceAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::InvoicesDelete), 403);

        try {
            $action->handle($invoice);
        } catch (ActionValidationException $exception) {
            return redirect()->route('invoices.index')
                ->withErrors($exception->errors());
        }

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

    private function getDefaultDocumentSubtype(?Workspace $workspace): ?DocumentSubtype
    {
        if ($workspace instanceof Workspace) {
            $workspacePreferred = $workspace->getPreferredDocumentSubtype();

            if ($workspacePreferred instanceof DocumentSubtype && $workspacePreferred->isValid()) {
                return $workspacePreferred;
            } else {

                return DocumentSubtype::active()
                    ->forInvoice()
                    ->where('is_default', 1)
                    ->first();

            }
        }

        return DocumentSubtype::active()
            ->forInvoice()
            ->where('is_default', true)
            ->first();
    }
}
