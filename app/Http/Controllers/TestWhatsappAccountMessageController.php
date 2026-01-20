<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class TestWhatsappAccountMessageController extends Controller
{
    /**
     * Send a test message.
     */
    public function __invoke(Request $request, WhatsappAccount $whatsappAccount): JsonResponse
    {
        $validated = $request->validate([
            'to' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        /** @var Response $response */
        $response = Http::withToken($whatsappAccount->access_token)
            ->post("https://graph.facebook.com/v21.0/{$whatsappAccount->phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $validated['to'],
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $validated['message'],
                ],
            ]);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'error' => $response->json('error.message', 'Failed to send message'),
            ], $response->status());
        }

        return response()->json([
            'success' => true,
            'message_id' => $response->json('messages.0.id'),
        ]);
    }
}
