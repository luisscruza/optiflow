<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class SharedPaymentPdfController
{
    public function __invoke(Payment $payment): Response
    {
        $payment->load([
            'bankAccount',
            'currency',
            'invoice.contact.primaryAddress',
            'invoice.documentSubtype',
            'invoice.workspace',
            'contact.primaryAddress',
            'lines',
            'withholdings',
        ]);

        $pdf = Pdf::loadView('payments.pdf', [
            'payment' => $payment,
            'company' => CompanyDetail::getAll(),
            'workspace' => $payment->invoice?->workspace,
        ])->setPaper('a4', 'portrait');

        activity()
            ->performedOn($payment)
            ->causedBy(null)
            ->withProperties(['payment_id' => $payment->id])
            ->log('Visualizó el recibo de pago en PDF compartido');

        return $pdf->stream("recibo-{$payment->payment_number}.pdf");
    }
}
