<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

final class ContactSearch
{
    /**
     * @return array<int, array{id: int, name: string, phone_primary: string|null, identification_number: string|null, email: string|null}>
     */
    public function searchCustomers(string $query, int $limit = 25): array
    {
        return $this->search($query, ['customer'], $limit);
    }

    /**
     * @param  array<int, string>  $contactTypes
     * @return array<int, array{id: int, name: string, phone_primary: string|null, identification_number: string|null, email: string|null}>
     */
    public function search(string $query, array $contactTypes = [], int $limit = 25, ?string $status = null): array
    {
        $search = mb_trim($query);

        if (mb_strlen($search) < 2) {
            return [];
        }

        return $this->baseQuery($contactTypes, $status)
            ->where(function (Builder $builder) use ($search): void {
                $like = "%{$search}%";

                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('phone_primary', 'like', $like)
                    ->orWhere('identification_number', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('mobile', 'like', $like);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Contact $contact): array => $this->toOption($contact))
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, name: string, phone_primary: string|null, identification_number: string|null, email: string|null}
     */
    public function toOption(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'phone_primary' => $contact->phone_primary,
            'identification_number' => $contact->identification_number,
            'email' => $contact->email,
        ];
    }

    /**
     * @return array{id: int, name: string, phone_primary: string|null, identification_number: string|null, email: string|null}|null
     */
    public function findCustomerById(?int $contactId): ?array
    {
        return $this->findById($contactId, ['customer']);
    }

    /**
     * @param  array<int, string>  $contactTypes
     * @return array{id: int, name: string, phone_primary: string|null, identification_number: string|null, email: string|null}|null
     */
    public function findById(?int $contactId, array $contactTypes = [], ?string $status = null): ?array
    {
        if (! is_int($contactId) || $contactId <= 0) {
            return null;
        }

        $contact = $this->baseQuery($contactTypes, $status)
            ->find($contactId);

        if (! $contact instanceof Contact) {
            return null;
        }

        return $this->toOption($contact);
    }

    /**
     * @param  array<int, string>  $contactTypes
     */
    private function baseQuery(array $contactTypes = [], ?string $status = null): Builder
    {
        $query = Contact::query()->select(['id', 'name', 'phone_primary', 'identification_number', 'email', 'mobile']);

        if ($contactTypes !== []) {
            $query->whereIn('contact_type', $contactTypes);
        }

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }
}
