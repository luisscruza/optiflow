<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateQuotationAction;
use App\Actions\UpdateQuotationAction;
use App\Enums\Permission;
use App\Enums\TaxType;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Quotation;
use App\Models\Tax;
use App\Models\User;
use App\Tables\QuotationsTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class QuotationController extends Controller
{
    /**
     * Display a listing of quotations.
     */
    public function index(Request $request, #[CurrentUser()] User $user): Response
    {
        abort_unless($user->can(Permission::QuotationsView), 403);

        return Inertia::render('quotations/index', [
            'quotations' => QuotationsTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::QuotationsCreate), 403);

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::query()
            ->active()
            ->forQuotation()
            ->orderBy('name')->get();

        $customers = Contact::customers()
            ->orderBy('name')
            ->get();

        // Include stock information to help users see available stock when creating quotations
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
            : DocumentSubtype::forQuotation()->active()->first();

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        // Group taxes by type for the multi-select component
        $taxesGroupedByType = Tax::query()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn ($taxes, $type): array => [
                $type => [
                    'label' => TaxType::tryFrom($type)?->label() ?? $type,
                    'isExclusive' => TaxType::tryFrom($type)?->isExclusive() ?? false,
                    'taxes' => $taxes->toArray(),
                ],
            ])
            ->toArray();

        return Inertia::render('quotations/create', [
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
            'taxesGroupedByType' => $taxesGroupedByType,
        ]);
    }

    /**
     * Store a newly created quotation.
     *
     * @throws Throwable
     */
    public function store(Request $request, #[CurrentUser] User $user, CreateQuotationAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::QuotationsCreate), 403);

        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $request->all());

        if ($result->isError()) {
            Session::flash('error', $result->error);

            return redirect()->route('quotations.create')
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('quotations.index')->with('success', 'Cotización creada exitosamente.');
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::QuotationsView), 403);

        $quotation->load(['contact', 'documentSubtype', 'items.product', 'items.taxes']);

        return Inertia::render('quotations/show', [
            'quotation' => $quotation,
        ]);
    }

    /**
     * Show the form for editing the specified quotation.
     */
    public function edit(Quotation $quotation, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::QuotationsEdit), 403);

        $quotation->load(['contact', 'documentSubtype', 'items.product', 'items.tax']);

        $documentSubtypes = DocumentSubtype::query()
            ->active()
            ->forQuotation()
            ->orderBy('name')->get();

        $customers = Contact::customers()->orderBy('name')->get();

        $currentWorkspace = Context::get('workspace');

        // Include stock information to help users see available stock when editing quotations
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

        // Group taxes by type for the multi-select component
        $taxesGroupedByType = Tax::query()
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('type')
            ->mapWithKeys(fn ($taxesGroup, $type): array => [
                $type => [
                    'label' => TaxType::tryFrom($type)?->label() ?? $type,
                    'isExclusive' => TaxType::tryFrom($type)?->isExclusive() ?? false,
                    'taxes' => $taxesGroup->toArray(),
                ],
            ])
            ->toArray();

        return Inertia::render('quotations/Edit', [
            'quotation' => $quotation,
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'taxes' => $taxes,
            'taxesGroupedByType' => $taxesGroupedByType,
        ]);
    }

    /**
     * Update the specified quotation.
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation, #[CurrentUser] User $user, UpdateQuotationAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::QuotationsEdit), 403);

        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $quotation, $request->validated());

        if ($result->isError()) {
            Session::flash('error', $result->error);

            return redirect()->route('quotations.edit', $quotation)
                ->withErrors(['error' => $result->error]);
        }

        return redirect()->route('quotations.show', $quotation)
            ->with('success', 'Cotización actualizada exitosamente.');
    }

    /**
     * Remove the specified quotation.
     */
    public function destroy(Quotation $quotation, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::QuotationsDelete), 403);

        // For now, we'll only allow deleting draft quotations
        if ($quotation->status !== 'draft') {
            return redirect()->back()->withErrors(['error' => 'Solo se pueden eliminar cotizaciones en borrador.']);
        }

        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', 'Cotización eliminada exitosamente.');
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
