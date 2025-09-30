<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePaymentAction;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;

final class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, CreatePaymentAction $action): RedirectResponse
    {
        $validatedData = $request->validated();

        $invoice = Invoice::find($validatedData['invoice_id']);

        $action->handle($invoice, $validatedData);

        return redirect()->back()->with('success', 'Pago registrado correctamente.');
    }
}
