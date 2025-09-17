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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): Response
    {
        $workspace = Context::get('workspace');

        $query = $workspace->contacts()
            ->with(['primaryAddress'])
            ->orderBy('name');

        if ($request->has('type') && in_array($request->type, ['customer', 'supplier'])) {
            $query->where('contact_type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('identification_number', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate(15)->withQueryString();

        return Inertia::render('contacts/index', [
            'contacts' => $contacts,
            'filters' => [
                'search' => $request->search,
                'type' => $request->type,
            ],
        ]);
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create(): Response
    {
        return Inertia::render('contacts/create', [
            'identification_types' => collect(IdentificationType::cases())
                ->map(fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
            'contact_types' => collect(ContactType::cases())
                ->map(fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
        ]);
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(CreateContactRequest $request, CreateContactAction $action, User $user): RedirectResponse
    {
        $contact = $action->handle($user, $request->validated());

        return redirect()->back()->with('newly_created_contact', $contact);
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact): Response
    {
        $contact->load(['primaryAddress', 'addresses']);

        return Inertia::render('contacts/show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact): Response
    {
        $contact->load(['primaryAddress']);

        return Inertia::render('contacts/edit', [
            'contact' => $contact,
            'identification_types' => collect(IdentificationType::cases())
                ->map(fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
            'contact_types' => collect(ContactType::cases())
                ->map(fn ($type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
        ]);
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(UpdateContactRequest $request, Contact $contact, UpdateContactAction $action, User $user): RedirectResponse
    {
        $action->handle($user, $contact, $request->validated());

        $contactTypeLabel = ContactType::from($contact->contact_type)->label();

        return redirect()->route('contacts.show', $contact)
            ->with('success', "El {$contactTypeLabel} ha sido actualizado exitosamente.");
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact, DeleteContactAction $action, User $user): RedirectResponse
    {
        $contactTypeLabel = ContactType::from($contact->contact_type)->label();

        $action->handle($user, $contact);

        return redirect()->route('contacts.index')
            ->with('success', "El {$contactTypeLabel} ha sido eliminado exitosamente.");
    }
}
