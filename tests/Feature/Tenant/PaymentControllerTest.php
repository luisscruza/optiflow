<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\Permission;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use App\Models\Workspace;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();
    $this->user = User::factory()->create([
        'current_workspace_id' => $workspace->id,
        'password_changed_at' => now(),
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
    $this->user->givePermissionTo(PermissionModel::findOrCreate(Permission::PaymentsCreate->value, 'web'));
});

function invoiceWithStatus(User $user, InvoiceStatus $status, float $total = 1000): Invoice
{
    return Invoice::factory()->create([
        'workspace_id' => $user->current_workspace_id,
        'status' => $status,
        'total_amount' => $total,
    ]);
}

test('create payment page lists invoices that still have a balance due', function (): void {
    $unpaid = invoiceWithStatus($this->user, InvoiceStatus::PendingPayment);
    $partiallyPaid = invoiceWithStatus($this->user, InvoiceStatus::PartiallyPaid);
    Payment::factory()->create(['invoice_id' => $partiallyPaid->id, 'amount' => 400]);

    $fullyPaid = invoiceWithStatus($this->user, InvoiceStatus::Paid);
    Payment::factory()->create(['invoice_id' => $fullyPaid->id, 'amount' => 1000]);

    foreach ([InvoiceStatus::Deleted, InvoiceStatus::Cancelled, InvoiceStatus::Draft] as $status) {
        invoiceWithStatus($this->user, $status);
    }

    $this->actingAs($this->user)
        ->get('/payments/create')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('payments/create')
            ->has('pendingInvoices', 2)
            ->where('pendingInvoices', fn ($invoices): bool => collect($invoices)->pluck('id')->sort()->values()->all() === collect([$unpaid->id, $partiallyPaid->id])->sort()->values()->all()));
});

test('a payment for the exact remaining balance is accepted despite float rounding', function (): void {
    $invoice = invoiceWithStatus($this->user, InvoiceStatus::PartiallyPaid, 1000.30);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 1000.10]);

    $bankAccount = BankAccount::factory()->create();

    $this->actingAs($this->user)
        ->post('/payments', [
            'payment_type' => PaymentType::InvoicePayment->value,
            'invoice_id' => $invoice->id,
            'bank_account_id' => $bankAccount->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::cases()[0]->value,
            'amount' => '0.20',
        ])
        ->assertSessionHasNoErrors();

    expect($invoice->payments()->count())->toBe(2)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});
