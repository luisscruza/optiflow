<?php

declare(strict_types=1);

use App\Actions\MoveWorkflowJobAction;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowJob;
use App\Models\WorkflowStage;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('starts an automation run when a job enters a stage', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    $user->current_workspace_id = $workspace->id;
    $user->save();

    $this->actingAs($user);

    $workflow = Workflow::query()->create([
        'name' => 'Test Workflow',
        'is_active' => true,
    ]);

    $fromStage = WorkflowStage::query()->create([
        'workflow_id' => $workflow->id,
        'name' => 'From',
        'description' => null,
        'color' => '#FFFFFF',
        'position' => 1,
        'is_active' => true,
        'is_initial' => true,
        'is_final' => false,
    ]);

    $toStage = WorkflowStage::query()->create([
        'workflow_id' => $workflow->id,
        'name' => 'To',
        'description' => null,
        'color' => '#FFFFFF',
        'position' => 2,
        'is_active' => true,
        'is_initial' => false,
        'is_final' => false,
    ]);

    $job = WorkflowJob::query()->create([
        'workflow_id' => $workflow->id,
        'workflow_stage_id' => $fromStage->id,
        'notes' => null,
        'priority' => null,
        'due_date' => null,
        'started_at' => null,
        'completed_at' => null,
        'canceled_at' => null,
    ]);

    expect($job->workspace_id)->toBe($workspace->id);

    $automation = Automation::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'When stage entered',
        'is_active' => true,
        'published_version' => 1,
    ]);

    AutomationVersion::query()->create([
        'automation_id' => $automation->id,
        'version' => 1,
        'definition' => [
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow.stage_entered',
                    'config' => [
                        'stage_id' => $toStage->id,
                    ],
                ],
                [
                    'id' => 'a1',
                    'type' => 'http.webhook',
                    'config' => [
                        'url' => 'https://example.test/hook',
                        'method' => 'POST',
                        'body' => [
                            'job_id' => '{{job.id}}',
                        ],
                    ],
                ],
            ],
            'edges' => [
                ['from' => 't1', 'to' => 'a1'],
            ],
        ],
        'created_by' => $user->id,
    ]);

    AutomationTrigger::query()->create([
        'automation_id' => $automation->id,
        'workspace_id' => $workspace->id,
        'event_key' => 'workflow.job.stage_changed',
        'workflow_id' => $workflow->id,
        'workflow_stage_id' => $toStage->id,
        'is_active' => true,
    ]);

    app(MoveWorkflowJobAction::class)->handle($job, $toStage);

    Queue::assertPushed(App\Jobs\ExecuteAutomationNodeJob::class);
});

it('executes http.webhook node using templates', function (): void {
    Http::fake([
        'example.test/*' => Http::response(['ok' => true], 200),
    ]);

    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
    $workspace->addUser($user, 'owner');

    $user->current_workspace_id = $workspace->id;
    $user->save();

    $this->actingAs($user);

    $workflow = Workflow::query()->create([
        'name' => 'Test Workflow',
        'is_active' => true,
    ]);

    $stage = WorkflowStage::query()->create([
        'workflow_id' => $workflow->id,
        'name' => 'Stage',
        'description' => null,
        'color' => '#FFFFFF',
        'position' => 1,
        'is_active' => true,
        'is_initial' => true,
        'is_final' => false,
    ]);

    $job = WorkflowJob::query()->create([
        'workflow_id' => $workflow->id,
        'workflow_stage_id' => $stage->id,
        'notes' => null,
        'priority' => null,
        'due_date' => null,
        'started_at' => null,
        'completed_at' => null,
        'canceled_at' => null,
    ]);

    $automation = Automation::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Webhook',
        'is_active' => true,
        'published_version' => 1,
    ]);

    $version = AutomationVersion::query()->create([
        'automation_id' => $automation->id,
        'version' => 1,
        'definition' => [
            'nodes' => [
                [
                    'id' => 'a1',
                    'type' => 'http.webhook',
                    'config' => [
                        'url' => 'https://example.test/hook',
                        'method' => 'POST',
                        'body' => [
                            'job_id' => '{{job.id}}',
                            'workspace_id' => '{{job.workspace_id}}',
                        ],
                    ],
                ],
            ],
            'edges' => [],
        ],
        'created_by' => $user->id,
    ]);

    $run = App\Models\AutomationRun::query()->create([
        'automation_id' => $automation->id,
        'automation_version_id' => $version->id,
        'workspace_id' => $workspace->id,
        'trigger_event_key' => 'manual',
        'subject_type' => 'workflow_job',
        'subject_id' => $job->id,
        'status' => 'running',
        'pending_nodes' => 1,
        'started_at' => now(),
    ]);

    (new App\Jobs\ExecuteAutomationNodeJob(
        automationRunId: $run->id,
        nodeId: 'a1',
        input: [
            'job' => [
                'id' => $job->id,
                'workspace_id' => $workspace->id,
            ],
        ],
    ))->handle(app(App\Services\Automation\NodeRunners\NodeRunnerRegistry::class));

    Http::assertSent(function (Illuminate\Http\Client\Request $request) use ($job, $workspace): bool {
        return $request->url() === 'https://example.test/hook'
            && $request['job_id'] === $job->id
            && $request['workspace_id'] === (string) $workspace->id;
    });
});
