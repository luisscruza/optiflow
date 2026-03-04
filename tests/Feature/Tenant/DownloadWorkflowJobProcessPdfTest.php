<?php

declare(strict_types=1);

use App\Models\CompanyDetail;
use App\Models\Contact;
use App\Models\Prescription;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use App\Models\Workspace;

test('workflow job process pdf streams successfully when prescription exists', function (): void {
    $user = User::factory()->create();

    CompanyDetail::setByKey('company_name', 'Optica Test');

    $workspace = Workspace::factory()->create([
        'name' => 'Sucursal Central',
        'address' => 'Ave. Principal 123',
        'phone' => '8090000000',
    ]);

    $patient = Contact::factory()->create(['name' => 'John Doe']);
    $optometrist = Contact::factory()->create(['name' => 'Dr. Smith']);

    $prescription = Prescription::factory()->create([
        'workspace_id' => $workspace->id,
        'patient_id' => $patient->id,
        'optometrist_id' => $optometrist->id,
        'subjetivo_od_esfera' => '-0.25',
        'subjetivo_od_cilindro' => '-0.50',
        'subjetivo_od_eje' => '20',
        'subjetivo_oi_esfera' => '-0.25',
        'subjetivo_oi_cilindro' => 'PL',
    ]);

    $workflow = Workflow::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_id' => $workflow->id]);

    $job = WorkflowJob::factory()->create([
        'workspace_id' => $workspace->id,
        'workflow_id' => $workflow->id,
        'workflow_stage_id' => $stage->id,
        'contact_id' => $patient->id,
        'prescription_id' => $prescription->id,
        'priority' => 'high',
        'due_date' => now()->addDays(7),
    ]);

    $response = $this->actingAs($user)
        ->get(route('workflows.jobs.process-pdf', [$workflow, $job]));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});

test('workflow job process pdf returns 404 when no prescription', function (): void {
    $user = User::factory()->create();

    $workflow = Workflow::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_id' => $workflow->id]);

    $job = WorkflowJob::factory()->create([
        'workflow_id' => $workflow->id,
        'workflow_stage_id' => $stage->id,
        'prescription_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('workflows.jobs.process-pdf', [$workflow, $job]));

    $response->assertNotFound();
});
