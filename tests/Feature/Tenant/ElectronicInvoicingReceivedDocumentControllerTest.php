<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    CompanyDetail::setByKey('easyfactu_environment', 'TesteCF');
    CompanyDetail::setByKey('easyfactu_api_key_testecf', 'ef_testecf_12345678901234567890123456789012');
    CompanyDetail::setByKey('easyfactu_base_url', 'https://api.easyfactu.test');
});

function makeUserWithWorkspace(): User
{
    $workspace = Workspace::factory()->create();

    return User::factory()->create([
        'current_workspace_id' => $workspace->id,
        'password_changed_at' => now(),
    ]);
}

test('index is forbidden without electronic invoicing permission', function (): void {
    $user = makeUserWithWorkspace();

    $response = $this->actingAs($user)->get(route('electronic-invoicing.received.index'));

    $response->assertForbidden();
});

test('index renders received documents when user has permission', function (): void {
    $user = makeUserWithWorkspace();

    $permission = PermissionFactory::new()->create([
        'name' => Permission::ElectronicInvoicingView->value,
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    Http::fake([
        'https://api.easyfactu.test/received-documents' => Http::response([
            'documents' => [
                [
                    'id' => 'rd_1',
                    'ecf_type' => '31',
                    'encf' => 'E310000000001',
                    'issue_date' => '2026-04-20',
                    'buyer_rnc' => '101010101',
                    'buyer_name' => 'Cliente',
                    'currency' => 'DOP',
                    'subtotal' => 100,
                    'tax_amount' => 18,
                    'total_amount' => 118,
                    'status' => 'received',
                    'received_at' => '2026-04-21T10:00:00Z',
                    'supplier' => [
                        'id' => 'sup_1',
                        'rnc' => '131313131',
                        'name' => 'Proveedor SRL',
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->get(route('electronic-invoicing.received.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('electronic-invoicing/received/index')
        ->has('documents', 1)
        ->where('documents.0.id', 'rd_1')
        ->where('error', null));
});

test('show renders a received document detail', function (): void {
    $user = makeUserWithWorkspace();

    $permission = PermissionFactory::new()->create([
        'name' => Permission::ElectronicInvoicingView->value,
        'guard_name' => 'web',
    ]);

    $user->givePermissionTo($permission);

    Http::fake([
        'https://api.easyfactu.test/received-documents/rd_1' => Http::response([
            'document' => [
                'id' => 'rd_1',
                'ecf_type' => '31',
                'encf' => 'E310000000001',
                'issue_date' => '2026-04-20',
                'buyer_rnc' => '101010101',
                'buyer_name' => 'Cliente',
                'currency' => 'DOP',
                'subtotal' => 100,
                'tax_amount' => 18,
                'total_amount' => 118,
                'status' => 'received',
                'received_at' => '2026-04-21T10:00:00Z',
                'created_at' => '2026-04-21T10:00:00Z',
                'updated_at' => '2026-04-21T10:00:00Z',
                'qr_code_url' => null,
                'security_code' => null,
                'signed_at' => null,
                'supplier' => [
                    'id' => 'sup_1',
                    'rnc' => '131313131',
                    'name' => 'Proveedor SRL',
                    'email' => 'proveedor@example.com',
                    'address' => 'Calle 1',
                    'phone' => '8090000000',
                ],
                'items' => [
                    [
                        'id' => 'item_1',
                        'line_number' => 1,
                        'description' => 'Servicio',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'tax_rate' => 18,
                        'subtotal' => 100,
                        'tax_amount' => 18,
                        'total_amount' => 118,
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->get(route('electronic-invoicing.received.show', ['receivedDocument' => 'rd_1']));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('electronic-invoicing/received/show')
        ->where('document.id', 'rd_1')
        ->has('document.items', 1));
});
