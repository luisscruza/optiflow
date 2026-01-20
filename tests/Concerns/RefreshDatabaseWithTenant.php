<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Central\Client;
use App\Models\Central\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\URL;

trait RefreshDatabaseWithTenant
{
    use RefreshDatabase {
        beginDatabaseTransaction as parentBeginDatabaseTransaction;
    }

    /**
     * The database connections that should have transactions.
     *
     * `null` is the default landlord connection, used for system-wide operations.
     * `tenant` is the tenant connection, specific to each tenant in the multi-tenant system.
     */
    protected array $connectionsToTransact = [null, 'tenant'];

    /**
     * We need to hook initialize tenancy _before_ we start the database
     * transaction, otherwise it cannot find the tenant connection.
     * This function initializes the tenant setup before starting a transaction.
     */
    public function beginDatabaseTransaction()
    {
        // Initialize tenant before beginning the database transaction.
        $this->initializeTenant();

        // Continue with the default database transaction setup.
        $this->parentBeginDatabaseTransaction();
    }

    /**
     * Initialize tenant for testing environment.
     * This function sets up a specific tenant for testing purposes.
     */
    public function initializeTenant()
    {
        $tenantId = 'foo';

        $tenant = Tenant::firstOr(function () use ($tenantId) {

            /**
             * Set the tenant prefix to the parallel testing token.
             * This is necessary to avoid database collisions when running tests in parallel.
             */
            config(['tenancy.database.prefix' => config('tenancy.database.prefix').ParallelTesting::token().'_']);

            $dbName = config('tenancy.database.prefix').$tenantId;

            DB::unprepared("DROP DATABASE IF EXISTS `{$dbName}`");

            $client = Client::firstOrCreate(
                ['name' => 'Test Client'],
                ['email' => 'test@example.com']
            );

            $t = Tenant::create(['id' => $tenantId, 'name' => 'Test Tenant', 'client_id' => $client->id, 'domain' => $tenantId]);

            if (! $t->domains()->count()) {
                $t->domains()->create(['domain' => $tenantId]);
            }

            return $t;
        });

        tenancy()->initialize($tenant);

        $subdomain = $tenant->domain;
        $url = 'https://'.$subdomain.'.opticanet.test';

        // Set the root URL for the current tenant.
        URL::forceRootUrl($url);
    }
}
