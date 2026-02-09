<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateQuotationAction;
use App\Actions\DeleteQuotationAction;
use App\Actions\UpdateQuotationAction;
use App\Enums\Permission;
use App\Enums\TaxType;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\DocumentSubtype;
use App\Models\Quotation;
use App\Models\Tax;
use App\Models\User;
use App\Support\ContactSearch;
use App\Support\ProductSearch;
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

final class QuotationController
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
    public function create(Request $request, #[CurrentUser] User $user, ContactSearch $contactSearch, ProductSearch $productSearch): Response
    {
        abort_unless($user->can(Permission::QuotationsCreate), 403);

        $currentWorkspace = Context::get('workspace');

        $documentSubtypes = DocumentSubtype::query()
            ->active()
            ->forQuotation()
            ->orderBy('name')->get();

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::query()->findOrFail($request->get('document_subtype_id'))
            : DocumentSubtype::forQuotation()->active()->first();

        $initialContactId = $request->integer('contact_id');
        $initialContact = $contactSearch->findCustomerById($initialContactId > 0 ? $initialContactId : null);

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
            'products' => [],
            'productSearchResults' => Inertia::optional(
                fn (): array => $productSearch->search((string) $request->string('product_search'), $currentWorkspace)
            ),
            'ncf' => $documentSubtype?->generateNCF(),
            'document_subtype_id' => $documentSubtype->id,
            'currentWorkspace' => $currentWorkspace,
            'availableWorkspaces' => $availableWorkspaces,
            'initialContact' => $initialContact,
            'customerSearchResults' => Inertia::optional(fn (): array => $contactSearch->searchCustomers((string) $request->string('contact_search'))),
            'taxesGroupedByType' => $taxesGroupedByType,
        ]);
    }

    /**
     * Store a newly created quotation.
     *
     * @throws Throwable
     */
    public function store(CreateQuotationRequest $request, #[CurrentUser] User $user, CreateQuotationAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::QuotationsCreate), 403);

        $workspace = Context::get('workspace');

        $result = $action->handle($workspace, $request->validated());

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
    public function edit(Request $request, Quotation $quotation, #[CurrentUser] User $user, ContactSearch $contactSearch, ProductSearch $productSearch): Response
    {
        abort_unless($user->can(Permission::QuotationsEdit), 403);

        $quotation->load(['contact', 'documentSubtype', 'items.product', 'items.taxes']);

        $documentSubtypes = DocumentSubtype::query()
            ->active()
            ->forQuotation()
            ->orderBy('name')->get();

        $currentWorkspace = Context::get('workspace');

        $initialProductIds = $quotation->items
            ->pluck('product_id')
            ->all();

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
            'products' => $productSearch->findByIds($initialProductIds, $currentWorkspace),
            'productSearchResults' => Inertia::optional(
                fn (): array => $productSearch->search((string) $request->string('product_search'), $currentWorkspace)
            ),
            'taxes' => $taxes,
            'taxesGroupedByType' => $taxesGroupedByType,
            'initialContact' => $quotation->contact ? $contactSearch->toOption($quotation->contact) : null,
            'customerSearchResults' => Inertia::optional(fn (): array => $contactSearch->searchCustomers((string) $request->string('contact_search'))),
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
    public function destroy(Quotation $quotation, #[CurrentUser] User $user, DeleteQuotationAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::QuotationsDelete), 403);

        try {
            $action->handle($quotation);
        } catch (ActionValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        return redirect()->route('quotations.index')
            ->with('success', 'Cotización eliminada exitosamente.');
    }
}
