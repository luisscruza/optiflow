<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateQuotationAction;
use App\Actions\UpdateQuotationAction;
use App\Enums\Permission;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Contact;
use App\Models\DocumentSubtype;
use App\Models\Product;
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

final class QuotationController extends Controller
{
    /**
     * Display a listing of quotations.
     */
    public function index(Request $request, #[CurrentUser()] User $user): Response
    {
        abort_unless($user->can(Permission::QuotationsView), 403);

        $query = Quotation::query()
            ->with(['contact', 'documentSubtype'])
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

        $quotations = $query->paginate(15)->withQueryString();

        return Inertia::render('quotations/index', [
            'quotations' => $quotations,
            'filters' => [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
            ],
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

        // For quotations, we don't need stock information since we don't track stock movements
        $products = Product::with(['defaultTax'])
            ->orderBy('name')
            ->get();

        $documentSubtype = $request->filled('document_subtype_id')
            ? DocumentSubtype::query()->findOrFail($request->get('document_subtype_id'))
            : DocumentSubtype::forQuotation()->active()->first();

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        return Inertia::render('quotations/create', [
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

        $quotation->load('contact', 'document_subtype', 'items.product', 'items.taxes');

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

        $workspace = Context::get('workspace');

        $quotation->load(['contact', 'documentSubtype', 'items.product', 'items.tax']);

        $documentSubtypes = DocumentSubtype::query()
            ->active()
            ->forQuotation()
            ->orderBy('name')->get();

        $customers = Contact::customers()->orderBy('name')->get();

        // For quotations, we don't need stock information
        $products = Product::with(['defaultTax'])
            ->orderBy('name')
            ->get();

        $taxes = Tax::query()->orderBy('name')->get();

        return Inertia::render('quotations/Edit', [
            'quotation' => $quotation,
            'documentSubtypes' => $documentSubtypes,
            'customers' => $customers,
            'products' => $products,
            'taxes' => $taxes,
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
}
