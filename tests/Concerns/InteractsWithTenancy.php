<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Central\Client;
use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\URL;

/**
 * More info: https://github.com/archtechx/tenancy/issues/250
 */
trait InteractsWithTenancy
{
    protected static ?Tenant $testTenant = null;

    protected static bool $tenantMigrated = false;

    /**
     * Initialize tenancy before each test.
     * Creates tenant on first use, then reuses it with transactions.
     * Must be called AFTER RefreshDatabase has run to ensure tenant exists.
     */
    public function setUpTenancy(): void
    {
        // Check if tenant exists (RefreshDatabase may have wiped it)
        if (static::$testTenant === null || !Tenant::find(static::$testTenant->id)) {
            static::$testTenant = $this->initializeTenant();
            static::$tenantMigrated = false;
        }

        // Initialize tenancy for this test
        tenancy()->initialize(static::$testTenant);

        // Set the default URL for route generation to use tenant subdomain
        $tenantDomain = static::$testTenant->domain.'.opticanet.test';
        URL::forceRootUrl('https://'.$tenantDomain);
        config(['app.url' => 'https://'.$tenantDomain]);

        // Set default server variables for HTTP requests
        $this->withServerVariables([
            'HTTP_HOST' => $tenantDomain,
            'SERVER_NAME' => $tenantDomain,
        ]);

        // Start a database transaction on the tenant connection
        DB::connection('tenant')->beginTransaction();
    }

    /**
     * Clean up after each test.
     */
    public function tearDownTenancy(): void
    {
        // Roll back the transaction to clean up test data
        if (DB::connection('tenant')->transactionLevel() > 0) {
            DB::connection('tenant')->rollBack();
        }

        tenancy()->end();
    }

    /**
     * Initialize tenant for testing with parallel testing support.
     * This method creates a tenant with a fixed ID and deletes/recreates the database.
     */
    protected function initializeTenant(): Tenant
    {
        // Hardcoded tenant ID for testing purposes
        $baseDomain = 'opticanet.test';
        $tenantId = 'pest';
        $tenantDomain = $tenantId.'.'.$baseDomain;

        /**
         * Set the tenant prefix to include the parallel testing token.
         * This is necessary to avoid database collisions when running tests in parallel.
         */
        $parallelToken = ParallelTesting::token();
        if ($parallelToken) {
            config(['tenancy.database.prefix' => config('tenancy.database.prefix').$parallelToken.'_']);
        }

        $dbName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix');
        $dbPath = database_path($dbName);

        // Delete existing tenant database to ensure fresh state
        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }

        // Find or create client
        $client = Client::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test Client',
        ]);

        // Create tenant (this will auto-create the domain via the booted() method)
        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => 'Test Tenant',
            'domain' => $tenantId, // Subdomain only, e.g., 'pest'
            'client_id' => $client->id,
        ]);

        if (! static::$tenantMigrated) {
            tenancy()->initialize($tenant);

            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
            ]);

            static::$tenantMigrated = true;

            tenancy()->end();
        }

                    URL::forceRootUrl("http://$tenantDomain");


        return $tenant;
    }
}
