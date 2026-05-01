<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Exceptions\EasyFactuException;
use App\Http\Requests\UpdateEasyFactuSettingsRequest;
use App\Models\CompanyDetail;
use App\Services\EasyFactuService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class EasyFactuSettingsController
{
    public function show(): Response
    {
        return Inertia::render('settings/electronic-invoicing', [
            'settings' => [
                'environment' => CompanyDetail::getByKey('easyfactu_environment', 'TesteCF'),
                'api_key_testecf' => $this->maskApiKey(CompanyDetail::getByKey('easyfactu_api_key_testecf')),
                'api_key_certecf' => $this->maskApiKey(CompanyDetail::getByKey('easyfactu_api_key_certecf')),
                'api_key_ecf' => $this->maskApiKey(CompanyDetail::getByKey('easyfactu_api_key_ecf')),
                'base_url' => CompanyDetail::getByKey('easyfactu_base_url', 'https://app.easyfactu.com/api'),
                'has_key_testecf' => CompanyDetail::getByKey('easyfactu_api_key_testecf') !== '',
                'has_key_certecf' => CompanyDetail::getByKey('easyfactu_api_key_certecf') !== '',
                'has_key_ecf' => CompanyDetail::getByKey('easyfactu_api_key_ecf') !== '',
            ],
        ]);
    }

    public function update(UpdateEasyFactuSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        CompanyDetail::setByKey('easyfactu_environment', $data['environment']);

        // Only update API keys if provided (non-empty means the user pasted a new key)
        if (! empty($data['api_key_testecf'])) {
            CompanyDetail::setByKey('easyfactu_api_key_testecf', $data['api_key_testecf']);
        }

        if (! empty($data['api_key_certecf'])) {
            CompanyDetail::setByKey('easyfactu_api_key_certecf', $data['api_key_certecf']);
        }

        if (! empty($data['api_key_ecf'])) {
            CompanyDetail::setByKey('easyfactu_api_key_ecf', $data['api_key_ecf']);
        }

        if (! empty($data['base_url'])) {
            CompanyDetail::setByKey('easyfactu_base_url', $data['base_url']);
        }

        return redirect()->back()->with('success', 'Configuración de facturación electrónica actualizada.');
    }

    public function testConnection(EasyFactuService $easyFactu): RedirectResponse
    {
        if (! $easyFactu->isConfigured()) {
            return redirect()->back()->with('error', 'Configura el entorno y la clave API antes de probar la conexión.');
        }

        try {
            $response = $easyFactu->getNextSequence('31');

            $nextEncf = $response['next_encf'] ?? $response['encf'] ?? 'N/A';

            return redirect()->back()->with('success', "Conexión exitosa. Próximo eNCF (E31): {$nextEncf}");
        } catch (EasyFactuException $e) {
            return redirect()->back()->with('error', 'Error de conexión: '.$e->getMessage());
        }
    }

    /**
     * Mask an API key for display, showing only the prefix and last 4 characters.
     */
    private function maskApiKey(string $key): string
    {
        if ($key === '' || mb_strlen($key) < 12) {
            return '';
        }

        $prefix = mb_substr($key, 0, 3); // "ef_"
        $suffix = mb_substr($key, -4);

        return $prefix.'***...'.$suffix;
    }
}
