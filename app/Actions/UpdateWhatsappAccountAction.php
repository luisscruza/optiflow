<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class UpdateWhatsappAccountAction
{
    public function handle(WhatsappAccount $whatsappAccount, array $data): WhatsappAccount
    {
        $updateData = [
            'name' => $data['name'],
            'phone_number_id' => $data['phone_number_id'],
            'business_account_id' => $data['business_account_id'] ?? null,
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['access_token'])) {
            $verification = $this->verifyCredentials($data['phone_number_id'], $data['access_token']);
            $updateData['access_token'] = $data['access_token'];
            $updateData['display_phone_number'] = $verification['display_phone_number'] ?? null;
        }

        return DB::transaction(function () use ($whatsappAccount, $updateData): WhatsappAccount {
            $whatsappAccount->update($updateData);

            return $whatsappAccount;
        });
    }

    /**
     * @return array{display_phone_number?: string}
     */
    private function verifyCredentials(string $phoneNumberId, string $accessToken): array
    {
        /** @var HttpResponse $response */
        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v21.0/{$phoneNumberId}");

        if (! $response->successful()) {
            throw new ActionValidationException([
                'access_token' => 'Credenciales inválidas: '.$response->json('error.message', 'Unknown error'),
            ]);
        }

        return [
            'display_phone_number' => $response->json('display_phone_number'),
        ];
    }
}
