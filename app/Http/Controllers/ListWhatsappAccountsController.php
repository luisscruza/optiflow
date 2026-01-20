<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\JsonResponse;

final class ListWhatsappAccountsController
{
    /**
     * List accounts for API/JSON consumption (automation builder).
     */
    public function __invoke(): JsonResponse
    {
        $accounts = WhatsappAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'display_phone_number', 'business_account_id']);

        return response()->json($accounts);
    }
}
