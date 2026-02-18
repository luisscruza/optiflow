<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class DownloadVisualCertificateController
{
    /**
     * Handle the incoming request.
     */
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
            'canalesDeReferimiento',
            'workspace',
            'patient',
            'optometrist',
        ]);

        $pdf = Pdf::loadView('prescriptions.visual-certificate', [
            'prescription' => $prescription,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $filename = "certificado-visual-{$prescription->patient->name}-{$prescription->id}.pdf";

        return $pdf->stream($filename);
    }
}
