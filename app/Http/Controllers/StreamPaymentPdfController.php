<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class StreamPaymentPdfController
{
    /**
     * Stream the payment receipt PDF inline for browser display.
     */
    public function __invoke(Payment $payment): Response
    {
        $payment->load([
            'bankAccount',
            'currency',
            'invoice.contact.primaryAddress',
            'invoice.documentSubtype',
            'contact.primaryAddress',
            'lines',
            'withholdings',
        ]);

        activity()
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties(['payment_id' => $payment->id])
            ->log('Descargó el recibo de pago en PDF');

        $workspace = Context::get('workspace');

        $pdf = Pdf::loadView('payments.pdf', [
            'payment' => $payment,
            'company' => CompanyDetail::getAll(),
            'workspace' => $workspace,
        ])->setPaper('a4', 'portrait');

        $filename = "recibo-{$payment->payment_number}.pdf";

        return $pdf->stream($filename);
    }
}
