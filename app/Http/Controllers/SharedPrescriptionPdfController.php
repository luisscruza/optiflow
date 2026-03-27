<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class SharedPrescriptionPdfController
{
    public function __invoke(Prescription $prescription): Response
    {
        if (! defined('DOMPDF_ENABLE_REMOTE')) {
            define('DOMPDF_ENABLE_REMOTE', false);
        }

        $prescription->load([
            'motivos',
            'estadoActual',
            'historiaOcularFamiliar',
            'lentesRecomendados',
            'gotasRecomendadas',
            'monturasRecomendadas',
            'workspace',
            'patient',
            'optometrist',
        ]);

        $pdf = Pdf::loadView('prescriptions.pdf', [
            'prescription' => $prescription,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("receta-{$prescription->patient->name}-{$prescription->id}.pdf");
    }
}
