<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\WorkflowFieldType;
use App\Models\CompanyDetail;
use App\Models\MastertableItem;
use App\Models\Workflow;
use App\Models\WorkflowField;
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




        $metadatas = $job->metadata;

        $fields = [];

        foreach ($metadatas as $key => $value) {
            $field = WorkflowField::where('workflow_id', $workflow->id)
                ->where('key', $key)
                ->first();

            if (! $field) {
                continue;
            }

            if ($field->type === WorkflowFieldType::Select) {
                $mastertableItem = MastertableItem::query()
                    ->where('mastertable_id', $field->mastertable_id)
                    ->where('id', $value)
                    ->first();

                $fields[$key] = [
                    'name' => $field->name,
                    'value' => $mastertableItem?->name ?? '-',
                ];
            } else {
                $fields[$key] = [
                    'name' => $field->name,
                    'value' => $value,
                ];
            }
        }

        $job->setAttribute('fields', $fields);

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
