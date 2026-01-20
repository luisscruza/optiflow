<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ActionValidationException;
use App\Models\WhatsappAccount;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class CreateWhatsappAccountAction
{
    public function handle(array $data): WhatsappAccount
    {
        $verification = $this->verifyCredentials($data['phone_number_id'], $data['access_token']);

        return DB::transaction(function () use ($data, $verification): WhatsappAccount {
            return WhatsappAccount::query()->create([
                'name' => $data['name'],
                'phone_number_id' => $data['phone_number_id'],
                'business_account_id' => $data['business_account_id'] ?? null,
                'access_token' => $data['access_token'],
                'display_phone_number' => $verification['display_phone_number'] ?? null,
                'is_active' => true,
            ]);
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
