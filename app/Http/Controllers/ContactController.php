<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateContactAction;
use App\Actions\DeleteContactAction;
use App\Actions\UpdateContactAction;
use App\Enums\ContactType;
use App\Enums\IdentificationType;
use App\Enums\Permission;
use App\Http\Requests\CreateContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\User;
use App\Support\ContactSearch;
use App\Tables\ContactsTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsView), 403);

        return Inertia::render('contacts/index', [
            'contacts' => ContactsTable::make($request),
        ]);
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create(Request $request, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsCreate), 403);

        return Inertia::render('contacts/create', [
            'identification_types' => collect(IdentificationType::cases())
                ->map(fn ($type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
            'contact_types' => collect(ContactType::cases())
                ->map(fn ($type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
            'relationshipSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchActive((string) $request->string('relationship_search'))
            ),
        ]);
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(CreateContactRequest $request, CreateContactAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsCreate), 403);

        $contact = $action->handle($user, $request->validated());

        return redirect()->back()->with('newly_created_contact', $contact);
    }

    /**
     * Display the specified contact.
     */
    public function show(Request $request, Contact $contact, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsView), 403);

        $contact->load([
            'primaryAddress',
            'addresses',
            'comments.commentator',
            'comments.comments.commentator',
            'comments.comments.comments.commentator',
            'relationships',
        ]);

        $contactIds = $contact->relatedContactIdsWithSelf();

        // Load invoices with basic info, limited to latest 10 (including related contacts)
        $invoices = \App\Models\Invoice::query()
            ->whereIn('contact_id', $contactIds)
            ->with(['documentSubtype', 'contact'])
            ->orderByDesc('issue_date')
            ->limit(10)
            ->get();

        // Load quotations from the Quotation model, limited to latest 10 (including related contacts)
        $quotations = \App\Models\Quotation::query()
            ->whereIn('contact_id', $contactIds)
            ->with(['documentSubtype', 'contact'])
            ->orderByDesc('issue_date')
            ->limit(10)
            ->get();

        // Load prescriptions, limited to latest 10
        $prescriptions = $contact->prescriptions()
            ->with(['optometrist'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Load workflow jobs, limited to latest 10
        $workflowJobs = $contact->workflowJobs()
            ->with(['workflow', 'workflowStage'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Calculate summary statistics
        $invoiceQuery = \App\Models\Invoice::query()->whereIn('contact_id', $contactIds);
        $quotationQuery = \App\Models\Quotation::query()->whereIn('contact_id', $contactIds);

        $stats = [
            'total_invoices' => (clone $invoiceQuery)->count(),
            'total_quotations' => (clone $quotationQuery)->count(),
            'total_prescriptions' => $contact->prescriptions()->count(),
            'total_workflow_jobs' => $contact->workflowJobs()->count(),
            'total_invoiced' => (clone $invoiceQuery)->sum('total_amount'),
            'total_paid' => (clone $invoiceQuery)
                ->whereIn('status', ['paid', 'partially_paid'])
                ->get()
                ->sum(fn ($invoice) => $invoice->total_amount - $invoice->amount_due),
            'pending_amount' => (clone $invoiceQuery)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->sum('total_amount'),
            'pending_workflow_jobs' => $contact->workflowJobs()
                ->whereNull('completed_at')
                ->whereNull('canceled_at')
                ->count(),
        ];

        return Inertia::render('contacts/show', [
            'contact' => $contact,
            'invoices' => $invoices,
            'quotations' => $quotations,
            'prescriptions' => $prescriptions,
            'workflowJobs' => $workflowJobs,
            'stats' => $stats,
            'relatedContacts' => $contact->relationships,
            'relationshipSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchActive(
                    (string) $request->string('relationship_search'),
                    $contact->id,
                )
            ),
        ]);
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Request $request, Contact $contact, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        $contact->load(['primaryAddress', 'relationships']);

        return Inertia::render('contacts/edit', [
            'contact' => $contact,
            'contact_relationships' => $contact->relationships->map(fn ($related): array => [
                'related_contact_id' => $related->id,
                'related_contact_name' => $related->name,
                'description' => $related->pivot?->description,
            ])->values()->toArray(),
            'relationshipSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchActive(
                    (string) $request->string('relationship_search'),
                    $contact->id
                )
            ),
            'identification_types' => collect(IdentificationType::cases())
                ->map(fn ($type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values()
                ->toArray(),
            'contact_types' => collect(ContactType::cases())
                ->map(fn ($type): array => [
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
    public function update(UpdateContactRequest $request, Contact $contact, UpdateContactAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        $action->handle($user, $contact, $request->validated());

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'El contacto ha sido actualizado exitosamente.');
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact, DeleteContactAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsDelete), 403);

        $action->handle($user, $contact);

        return redirect()->route('contacts.index')
            ->with('success', 'El contacto ha sido eliminado exitosamente.');
    }
}
