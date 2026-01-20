<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class SendTestWhatsappAccountMessageAction
{
    /**
     * @return array{success: bool, message_id: string|null}
     */
    public function handle(WhatsappAccount $whatsappAccount, string $to, string $message): array
    {
        /** @var Response $response */
        $response = Http::withToken($whatsappAccount->access_token)
            ->post("https://graph.facebook.com/v21.0/{$whatsappAccount->phone_number_id}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        if (! $response->successful()) {
            throw new ActionValidationException([
                'error' => $response->json('error.message', 'Failed to send message'),
            ]);
        }

        return [
            'success' => true,
            'message_id' => $response->json('messages.0.id'),
        ];
    }
}
