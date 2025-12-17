<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePaymentAction;
use App\Actions\DeletePaymentAction;
use App\Actions\UpdatePaymentAction;
use App\Enums\Permission;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, #[CurrentUser] User $user, CreatePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsCreate), 403);

        $validatedData = $request->validated();

        $invoice = Invoice::query()->find($validatedData['invoice_id']);

        $action->handle($invoice, $validatedData);

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }

    public function update(UpdatePaymentRequest $request, Payment $payment, #[CurrentUser] User $user, UpdatePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsEdit), 403);

        $action->handle($payment, $request->validated());

        return redirect()->back()->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy(Payment $payment, #[CurrentUser] User $user, DeletePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsDelete), 403);

        $action->handle($payment);

        return redirect()->back()->with('success', 'Pago eliminado correctamente.');
    }
}
