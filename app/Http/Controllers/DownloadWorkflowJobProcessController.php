<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyDetail;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class DownloadWorkflowJobProcessController
{
    /**
     * Handle the incoming request to generate the workflow job process PDF.
     */
    public function __invoke(Workflow $workflow, WorkflowJob $job): Response
    {
        if (! defined('DOMPDF_ENABLE_REMOTE')) {
            define('DOMPDF_ENABLE_REMOTE', false);
        }

        $job->load([
            'contact',
            'workspace',
            'prescription.patient',
            'prescription.optometrist',
            'prescription.workspace',
            'prescription.lentesRecomendados',
        ]);

        $prescription = $job->prescription;

        abort_unless($prescription !== null, 404, 'Este proceso no tiene una receta asociada.');

        $pdf = Pdf::loadView('workflow-jobs.process-pdf', [
            'job' => $job,
            'prescription' => $prescription,
            'company' => CompanyDetail::getAll(),
        ])->setPaper('a4', 'portrait');

        $clientName = $job->contact?->name ?? $prescription->patient?->name ?? 'N-A';
        $filename = "proceso-{$clientName}-{$prescription->id}.pdf";

        return $pdf->stream($filename);
    }
}
