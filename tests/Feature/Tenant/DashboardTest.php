<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Database\Factories\PermissionFactory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create(['current_workspace_id' => $workspace->id]);
});

test('dashboard includes contacts by lead source widget data', function (): void {
    $permission = PermissionFactory::new()->create([
        'name' => Permission::ViewDashboardCustomersStats->value,
        'guard_name' => 'web',
    ]);

    $this->user->givePermissionTo($permission);

    $leadSources = Mastertable::query()
        ->where('alias', Contact::LEAD_SOURCES_MASTERTABLE_ALIAS)
        ->firstOrFail()
        ->items()
        ->orderBy('id')
        ->get();

    Contact::factory()->count(2)->create([
        'lead_source_id' => $leadSources[0]->id,
        'created_at' => Carbon::now()->startOfMonth()->addDay(),
    ]);

    Contact::factory()->count(1)->create([
        'lead_source_id' => $leadSources[1]->id,
        'created_at' => Carbon::now()->startOfMonth()->addDays(2),
    ]);

    Contact::factory()->count(1)->create([
        'lead_source_id' => null,
        'created_at' => Carbon::now()->startOfMonth()->addDays(3),
    ]);

    Contact::factory()->create([
        'lead_source_id' => $leadSources[0]->id,
        'created_at' => Carbon::now()->subMonths(2),
    ]);

    $response = $this->actingAs($this->user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('availableWidgets.contacts-by-lead-source', 'Contactos')
        ->where('contactsByLeadSource.total', 4)
        ->has('contactsByLeadSource.sources', 3)
        ->where('contactsByLeadSource.sources.0.label', $leadSources[0]->name)
        ->where('contactsByLeadSource.sources.0.count', 2)
        ->where('contactsByLeadSource.sources.1.label', $leadSources[1]->name)
        ->where('contactsByLeadSource.sources.1.count', 1)
        ->where('contactsByLeadSource.sources.2.label', 'Sin procedencia')
        ->where('contactsByLeadSource.sources.2.count', 1));
});
