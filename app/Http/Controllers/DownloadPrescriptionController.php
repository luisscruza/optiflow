<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class DownloadPrescriptionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Prescription $prescription): Response
    {
        define('DOMPDF_ENABLE_REMOTE', false);

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
        ])->setPaper('a4', 'landscape');

        $filename = "prescription-{$prescription->id}.pdf";

        return $pdf->stream($filename);
    }
}
