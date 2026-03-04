<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateContactAction
{
    /**
     * Update a contact.
     */
    public function handle(User $user, Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            // Extract nested data if present
            $addressData = $data['address'] ?? null;
            $relationships = $data['relationships'] ?? [];
            unset($data['address'], $data['relationships']);

            // Update the contact
            $contact->update($data);

            // Update or create the primary address if provided
            if ($addressData && array_filter($addressData) !== []) {
                $primaryAddress = $contact->addresses()->first();

                if ($primaryAddress) {
                    $primaryAddress->update($addressData);
                } else {
                    $contact->addresses()->create($addressData);
                }
            }

            $this->syncRelationships($contact, $relationships);

            return $contact->fresh(['addresses', 'relationships']);
        });
    }

    /**
     * @param  array<int, array{related_contact_id:int, description?:string|null}>  $relationships
     */
    private function syncRelationships(Contact $contact, array $relationships): void
    {
        $syncData = [];

        foreach ($relationships as $relationship) {
            $relatedContactId = (int) ($relationship['related_contact_id'] ?? 0);
            if ($relatedContactId <= 0 || $relatedContactId === $contact->id) {
                continue;
            }

            $description = isset($relationship['description']) ? mb_trim((string) $relationship['description']) : null;
            $syncData[$relatedContactId] = ['description' => $description !== '' ? $description : null];
        }

        $existingRelatedIds = $contact->relationships()->pluck('contacts.id')->map(fn ($id) => (int) $id)->all();

        $contact->relationships()->sync($syncData);

        foreach ($existingRelatedIds as $existingRelatedId) {
            if (! array_key_exists($existingRelatedId, $syncData)) {
                $existingRelatedContact = Contact::query()->find($existingRelatedId);
                $existingRelatedContact?->relationships()->detach($contact->id);
            }
        }

        foreach ($syncData as $relatedContactId => $pivotData) {
            $relatedContact = Contact::query()->find((int) $relatedContactId);
            if (! $relatedContact) {
                continue;
            }

            $relatedContact->relationships()->syncWithoutDetaching([
                $contact->id => $pivotData,
            ]);
        }
    }
}
