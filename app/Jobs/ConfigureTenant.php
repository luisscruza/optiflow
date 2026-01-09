<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CreateWorkspaceAction;
use App\Enums\BankAccountType;
use App\Enums\Permission as EnumsPermission;
use App\Enums\UserRole;
use App\Models\BankAccount;
use App\Models\Central\Client;
use App\Models\CompanyDetail;
use App\Models\Currency;
use App\Models\Permission;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WelcomeEmailNotification;
use App\Support\Slug;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Str;

final class ConfigureTenant implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private TenantWithDatabase $tenant)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var Client $client */
        $client = $this->tenant->client;

        $this->tenant->run(function () use ($client): void {
            $user = $this->createMainUser($client);
            $workspace = $this->createMainWorkspace($user);
            $this->setCompanyDetails($this->tenant, $client);
            $this->createRoles($user, $workspace);
            $this->createBankAccounts();
        });
    }

    private function createMainUser(Client $client): User
    {
        $password = Str::random(12);

        $user = User::query()->create([
            'name' => $client->name,
            'email' => $client->email,
            'password' => Hash::make($password),
            'business_role' => UserRole::Admin,
        ]);

        $user->notify(new WelcomeEmailNotification($password, $this->tenant->domain));

        return $user;
    }

    private function createMainWorkspace(User $user): Workspace
    {
        return app(CreateWorkspaceAction::class)->handle($user, [
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

    private function createRoles(User $user, Workspace $workspace): void
    {
        $allPermissions = Permission::all()->pluck('name')->toArray();
        $adminOnlyPermissions = EnumsPermission::adminOnly();
        $userPermissions = array_diff($allPermissions, $adminOnlyPermissions);

        $admin = $this->createRole('Administrador', $workspace->id);
        $admin->syncPermissions($allPermissions);

        $userRole = $this->createRole('Usuario', $workspace->id);
        $userRole->syncPermissions($userPermissions);

        app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);

        $user->assignRole($admin);
    }

    private function createBankAccounts(): void
    {
        BankAccount::query()->create([
            'name' => 'Caja general',
            'type' => BankAccountType::Cash,
            'currency_id' => Currency::query()->where('is_default', true)->first()->id,
            'account_number' => '000-000-000',
            'initial_balance' => 0,
            'initial_balance_date' => now(),
            'is_system_account' => false,
            'is_active' => true,
            'balance' => 0,
            'description' => 'Cuenta de caja general creada por defecto',
        ]);
    }

    private function createRole(string $name, int $workspaceId): Role
    {
        return Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'workspace_id' => $workspaceId,
        ]);
    }
}
