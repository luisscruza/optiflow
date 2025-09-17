<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateContactAction;
use App\Actions\DeleteContactAction;
use App\Actions\UpdateContactAction;
use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Http\Requests\CreateContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\User;
use Context;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(User $user): Response
    {
        $workspace = Context::get('workspace');

        $suppliers = $workspace->contacts()
            ->suppliers()
            ->with(['primaryAddress'])
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('identification_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('suppliers/index', [
            'suppliers' => $suppliers,
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): Response
    {
        return Inertia::render('suppliers/create', [
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(CreateContactRequest $request, CreateContactAction $createContact, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['contact_type'] = ContactType::Supplier->value;

        $supplier = $createContact->handle($user, $data);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'proveedor creado exitosamente.');
    }

    /**
     * Display the specified supplier.
     */
    public function show(Contact $supplier): Response
    {
        // Ensure this is a supplier
        if (! $supplier->isSupplier()) {
            abort(404);
        }

        $supplier->load(['addresses', 'primaryAddress', 'suppliedStocks.product']);

        return Inertia::render('suppliers/show', [
            'supplier' => $supplier,
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Contact $supplier): Response
    {
        // Ensure this is a supplier
        if (! $supplier->isSupplier()) {
            abort(404);
        }

        $supplier->load(['addresses', 'primaryAddress']);

        return Inertia::render('suppliers/edit', [
            'supplier' => $supplier,
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateContactRequest $request, Contact $supplier, UpdateContactAction $updateContact, User $user): RedirectResponse
    {
        // Ensure this is a supplier
        if (! $supplier->isSupplier()) {
            abort(404);
        }

        $data = $request->validated();
        $data['contact_type'] = ContactType::Supplier->value;

        $updateContact->handle($user, $supplier, $data);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Contact $supplier, DeleteContactAction $deleteContact, User $user): RedirectResponse
    {
        // Ensure this is a supplier
        if (! $supplier->isSupplier()) {
            abort(404);
        }

        $deleteContact->handle($user, $supplier);

        return redirect()->route('suppliers.index')
            ->with('success', 'proveedor eliminado exitosamente.');
    }
}
