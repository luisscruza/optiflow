<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckContactDuplicatesController
{
    /**
     * Check for potential duplicate contacts by email or phone.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->query('email');
        $phone = $request->query('phone');
        $excludeId = $request->query('exclude_id');

        $duplicates = [];

        if (filled($email)) {
            $duplicate = Contact::query()
                ->select(['id', 'name', 'email', 'phone_primary'])
                ->where('email', $email)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->first();

            if ($duplicate) {
                $duplicates['email'] = [
                    'id' => $duplicate->id,
                    'name' => $duplicate->name,
                ];
            }
        }

        if (filled($phone)) {
            $duplicate = Contact::query()
                ->select(['id', 'name', 'email', 'phone_primary'])
                ->where('phone_primary', $phone)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->first();

            if ($duplicate) {
                $duplicates['phone'] = [
                    'id' => $duplicate->id,
                    'name' => $duplicate->name,
                ];
            }
        }

        return response()->json($duplicates);
    }
}
