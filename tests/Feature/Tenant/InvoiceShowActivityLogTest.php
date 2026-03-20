<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\Permission;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Workspace;
use Database\Factories\PermissionFactory;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $workspace = Workspace::factory()->create();

    $this->user = User::factory()->create([
        'current_workspace_id' => $workspace->id,
    ]);

    $this->user->givePermissionTo(PermissionFactory::new()->create([
        'name' => Permission::InvoicesView->value,
        'guard_name' => 'web',
    ]));
});

test('invoice history keeps deleted payment activity', function (): void {
    $currency = Currency::factory()->create();
    $bankAccount = BankAccount::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $invoice = Invoice::factory()->create([
        'workspace_id' => $this->user->current_workspace_id,
        'currency_id' => $currency->id,
        'status' => 'pending_payment',
        'total_amount' => 100,
        'subtotal_amount' => 100,
        'tax_amount' => 0,
        'discount_amount' => 0,
    ]);

    $payment = Payment::factory()->create([
        'bank_account_id' => $bankAccount->id,
        'currency_id' => $currency->id,
        'invoice_id' => $invoice->id,
        'payment_type' => PaymentType::InvoicePayment,
        'payment_method' => PaymentMethod::Cash,
        'status' => PaymentStatus::Completed,
        'amount' => 50,
        'subtotal_amount' => 50,
        'tax_amount' => 0,
        'withholding_amount' => 0,
    ]);

    $payment->delete();
    $invoice->refresh()->updatePaymentStatus();

    $this->assertSoftDeleted('payments', [
        'id' => $payment->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('invoices.show', $invoice));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('invoices/show')
        ->has('activities', 2)
        ->where('activities.0.description', 'Pago registrado')
        ->where('activities.1.description', 'Pago eliminado')
        ->where('activities.1.event', 'deleted')
    );
});
