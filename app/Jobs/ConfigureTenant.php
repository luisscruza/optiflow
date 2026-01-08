<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateWorkspaceAction;
use App\Enums\UserRole;
use App\Models\Central\Client;
use App\Models\CompanyDetail;
use App\Models\Currency;
use App\Models\User;
use App\Models\Workspace;
use App\Support\Slug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

final class ConfigureTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private TenantWithDatabase $tenant) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Client $client */
        $client = $this->tenant->client;

        $this->tenant->run(function () use ($client): void {
            $user = $this->createMainUser($client);
            $this->createMainWorkspace($user);
            $this->setCompanyDetails($this->tenant, $client);
        });
    }

    private function createMainUser(Client $client): User
    {
        return User::query()->create([
            'name' => $client->name,
            'email' => $client->email,
            'password' => Hash::make('password'),
            'business_role' => UserRole::Admin,
        ]);
    }

    private function createMainWorkspace(User $user): void
    {
        app(CreateWorkspaceAction::class)->handle($user, [
            'name' => 'Principal',
            'code' => mb_strtoupper(Slug::generateUniqueSlug($user->name, Workspace::class)),
            'description' => 'Sucursal principal',
            'is_default' => true,
        ]);
    }

    private function setCompanyDetails(TenantWithDatabase $tenant, Client $client): void
    {
        $values = [
            [
                'key' => 'company_name',
                'value' => $tenant->name,
            ],
            [
                'key' => 'email',
                'value' => $client->email,
            ],
            [
                'key' => 'currency',
                'value' => Currency::query()->where('is_default', true)->first()->id,
            ],
            [
                'key' => 'address',
                'value' => '',
            ],
            [
                'key' => 'phone',
                'value' => $client->phone_primary ?? '',
            ],
            [
                'key' => 'tax_id',
                'value' => '',
            ],
        ];

        foreach ($values as $setting) {
            CompanyDetail::setByKey($setting['key'], $setting['value']);
        }
    }
}
