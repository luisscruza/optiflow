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
    public function create(#[CurrentUser] User $user): Response
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
    public function show(Contact $contact, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsView), 403);

        $contact->load([
            'primaryAddress',
            'addresses',
            'comments.commentator',
            'comments.comments.commentator',
            'comments.comments.comments.commentator',
        ]);

        // Load invoices with basic info, limited to latest 10
        $invoices = $contact->invoices()
            ->with(['documentSubtype'])
            ->orderByDesc('issue_date')
            ->limit(10)
            ->get();

        // Load quotations from the Quotation model, limited to latest 10
        $quotations = $contact->quotations()
            ->with(['documentSubtype'])
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
        $stats = [
            'total_invoices' => $contact->invoices()->count(),
            'total_quotations' => $contact->quotations()->count(),
            'total_prescriptions' => $contact->prescriptions()->count(),
            'total_workflow_jobs' => $contact->workflowJobs()->count(),
            'total_invoiced' => $contact->invoices()->sum('total_amount'),
            'total_paid' => $contact->invoices()
                ->whereIn('status', ['paid', 'partially_paid'])
                ->get()
                ->sum(fn ($invoice) => $invoice->total_amount - $invoice->amount_due),
            'pending_amount' => $contact->invoices()
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
        ]);
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        $contact->load(['primaryAddress']);

        return Inertia::render('contacts/edit', [
            'contact' => $contact,
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
