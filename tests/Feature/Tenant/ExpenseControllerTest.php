<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->workspace = Workspace::factory()->create();
    $this->otherWorkspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'current_workspace_id' => $this->workspace->id,
    ]);
});

function grantExpensePermissions(User $user, Permission ...$permissions): void
{
    foreach ($permissions as $permission) {
        $permissionModel = PermissionFactory::new()->create([
            'name' => $permission->value,
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permissionModel);
    }
}

test('it stores an expense with attachments', function (): void {
    grantExpensePermissions($this->user, Permission::ExpensesCreate, Permission::ExpensesView);

    $supplier = App\Models\Contact::factory()->supplier()->create();

    $response = $this->actingAs($this->user)->post('/expenses', [
        'workspace_id' => $this->workspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000001',
        'issue_date' => '2026-04-22',
        'subtotal_amount' => 1000,
        'itbis_amount' => 180,
        'isc_amount' => 0,
        'withheld_itbis_amount' => 0,
        'withheld_isr_amount' => 0,
        'is_informal' => true,
        'status' => 'pending',
        'attachments' => [
            UploadedFile::fake()->create('factura.pdf', 200, 'application/pdf'),
        ],
    ]);

    $expense = Expense::query()->withoutWorkspaceScope()->where('document_number', 'B0100000001')->firstOrFail();

    $response->assertRedirect("/expenses/{$expense->id}");

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'workspace_id' => $this->workspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000001',
        'status' => 'pending',
        'is_informal' => true,
        'total_amount' => '1180.00',
    ]);

    expect($expense->getMedia('attachments'))->toHaveCount(1);
});

test('it prevents duplicate expenses for the same workspace supplier and document number', function (): void {
    grantExpensePermissions($this->user, Permission::ExpensesCreate);

    $supplier = App\Models\Contact::factory()->supplier()->create();

    Expense::factory()->create([
        'workspace_id' => $this->workspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000002',
    ]);

    $response = $this->actingAs($this->user)->post('/expenses', [
        'workspace_id' => $this->workspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000002',
        'issue_date' => '2026-04-22',
        'subtotal_amount' => 1000,
        'itbis_amount' => 180,
        'isc_amount' => 0,
        'withheld_itbis_amount' => 0,
        'withheld_isr_amount' => 0,
        'is_informal' => false,
        'status' => 'pending',
    ]);

    $response->assertSessionHasErrors('document_number');
});

test('user without view all locations cannot create expenses in another workspace', function (): void {
    grantExpensePermissions($this->user, Permission::ExpensesCreate);

    $supplier = App\Models\Contact::factory()->supplier()->create();

    $response = $this->actingAs($this->user)->post('/expenses', [
        'workspace_id' => $this->otherWorkspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000003',
        'issue_date' => '2026-04-22',
        'subtotal_amount' => 1000,
        'itbis_amount' => 180,
        'isc_amount' => 0,
        'withheld_itbis_amount' => 0,
        'withheld_isr_amount' => 0,
        'is_informal' => false,
        'status' => 'pending',
    ]);

    $response->assertSessionHasErrors('workspace_id');

    $this->assertDatabaseMissing('expenses', [
        'document_number' => 'B0100000003',
    ]);
});

test('user with view all locations can create expenses in another workspace', function (): void {
    grantExpensePermissions($this->user, Permission::ExpensesCreate, Permission::ViewAllLocations);

    $supplier = App\Models\Contact::factory()->supplier()->create();

    $response = $this->actingAs($this->user)->post('/expenses', [
        'workspace_id' => $this->otherWorkspace->id,
        'contact_id' => $supplier->id,
        'document_number' => 'B0100000004',
        'issue_date' => '2026-04-22',
        'subtotal_amount' => 1000,
        'itbis_amount' => 180,
        'isc_amount' => 0,
        'withheld_itbis_amount' => 0,
        'withheld_isr_amount' => 0,
        'is_informal' => false,
        'status' => 'pending',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('expenses', [
        'workspace_id' => $this->otherWorkspace->id,
        'document_number' => 'B0100000004',
    ]);
});

test('user with view all locations can list expenses from every workspace', function (): void {
    grantExpensePermissions($this->user, Permission::ExpensesView, Permission::ViewAllLocations);

    Expense::factory()->create([
        'workspace_id' => $this->workspace->id,
        'document_number' => 'B0100000010',
    ]);

    Expense::factory()->create([
        'workspace_id' => $this->otherWorkspace->id,
        'document_number' => 'B0100000011',
    ]);

    $response = $this->actingAs($this->user)->get('/expenses');

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('expenses.data.total', 2)
        ->has('expenses.data.data', 2));
});
