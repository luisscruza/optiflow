<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Mastertable;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'current_workspace_id' => $workspace->id,
        'password_changed_at' => now(),
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
});

test('dashboard includes contacts by lead source widget data', function (): void {
    $permission = PermissionModel::findOrCreate(Permission::ViewDashboardCustomersStats->value, 'web');

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

test('dashboard excludes voided and cancelled invoices from every sales indicator', function (): void {
    $contact = Contact::factory()->create();
    $issueDate = Carbon::now()->startOfMonth()->addDay();

    $activeInvoice = Invoice::factory()->create([
        'workspace_id' => $this->user->current_workspace_id,
        'contact_id' => $contact->id,
        'status' => InvoiceStatus::PendingPayment,
        'issue_date' => $issueDate,
        'due_date' => Carbon::now()->addMonth(),
        'total_amount' => 1000,
        'tax_amount' => 180,
    ]);
    InvoiceItem::factory()->create(['invoice_id' => $activeInvoice->id]);

    foreach ([InvoiceStatus::Deleted, InvoiceStatus::Cancelled] as $status) {
        $voidedInvoice = Invoice::factory()->create([
            'workspace_id' => $this->user->current_workspace_id,
            'status' => $status,
            'issue_date' => $issueDate,
            'due_date' => Carbon::now()->addMonth(),
            'total_amount' => 500,
            'tax_amount' => 90,
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $voidedInvoice->id]);
    }

    $response = $this->actingAs($this->user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('totalSales.total', fn ($total): bool => (float) $total === 1000.0)
        ->where('salesTax.amount', fn ($amount): bool => (float) $amount === 180.0)
        ->where('customersWithSales.count', 1)
        ->where('productsSold.count', 1)
        ->where('accountsReceivable.total.amount', fn ($amount): bool => (float) $amount === 1000.0)
        ->where('accountsReceivable.total.count', 1));
});
