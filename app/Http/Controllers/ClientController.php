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

final class ClientController extends Controller
{
    /**
     * Display a listing of clients.
     */
    public function index(User $user): Response
    {
        $workspace = Context::get('workspace');

        $clients = $workspace->contacts()
            ->customers()
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

        return Inertia::render('clients/index', [
            'clients' => $clients,
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new client.
     */
    public function create(): Response
    {
        return Inertia::render('clients/create', [
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(CreateContactRequest $request, CreateContactAction $createContact, User $user): RedirectResponse
    {
        $data = $request->validated();
        $data['contact_type'] = ContactType::Customer->value;

        $client = $createContact->handle($user, $data);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified client.
     */
    public function show(Contact $client): Response
    {
        $client->load(['addresses', 'primaryAddress', 'documents.documentItems.product']);

        return Inertia::render('clients/show', [
            'client' => $client,
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Contact $client): Response
    {
        // Ensure this is a customer
        if (! $client->isCustomer()) {
            abort(404);
        }

        $client->load(['addresses', 'primaryAddress']);

        return Inertia::render('clients/edit', [
            'client' => $client,
            'identificationTypes' => IdentificationType::options(),
        ]);
    }

    /**
     * Update the specified client.
     */
    public function update(UpdateContactRequest $request, Contact $client, UpdateContactAction $updateContact, User $user): RedirectResponse
    {
        // Ensure this is a customer
        if (! $client->isCustomer()) {
            abort(404);
        }

        $data = $request->validated();
        $data['contact_type'] = ContactType::Customer->value;

        $updateContact->handle($user, $client, $data);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified client.
     */
    public function destroy(Contact $client, DeleteContactAction $deleteContact, User $user): RedirectResponse
    {
        // Ensure this is a customer
        if (! $client->isCustomer()) {
            abort(404);
        }

        $deleteContact->handle($user, $client);

        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }
}
