<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\User;
use App\Support\ContactSearch;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class ContactRelationshipController
{
    /**
     * Show the relationship management page for a contact (used for search via Inertia partial reload).
     */
    public function create(Request $request, Contact $contact, ContactSearch $contactSearch, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        $contact->load('relationships');

        return Inertia::render('contacts/show', [
            'relationshipSearchResults' => Inertia::optional(
                fn (): array => $contactSearch->searchActive(
                    (string) $request->string('relationship_search'),
                    $contact->id
                )
            ),
        ]);
    }

    /**
     * Store a new relationship for a contact.
     */
    public function store(Request $request, Contact $contact, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        $validated = $request->validate([
            'related_contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id'),
                Rule::notIn([$contact->id]),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $relatedContactId = (int) $validated['related_contact_id'];
        $description = isset($validated['description']) && mb_trim($validated['description']) !== ''
            ? mb_trim($validated['description'])
            : null;

        // Add the relationship in both directions
        $contact->relationships()->syncWithoutDetaching([
            $relatedContactId => ['description' => $description],
        ]);

        $relatedContact = Contact::query()->findOrFail($relatedContactId);
        $relatedContact->relationships()->syncWithoutDetaching([
            $contact->id => ['description' => $description],
        ]);

        return redirect()->back()->with('success', 'Relación agregada exitosamente.');
    }

    /**
     * Remove a relationship from a contact.
     */
    public function destroy(Contact $contact, Contact $related, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        // Remove the relationship in both directions
        $contact->relationships()->detach($related->id);
        $related->relationships()->detach($contact->id);

        return redirect()->back()->with('success', 'Relación eliminada exitosamente.');
    }
}
