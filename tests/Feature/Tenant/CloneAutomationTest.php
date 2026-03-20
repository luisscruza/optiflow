<?php

declare(strict_types=1);

use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationVersion;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->sourceWorkspace = Workspace::factory()->create([
        'name' => 'Sucursal Centro',
    ]);

    $this->targetWorkspace = Workspace::factory()->create([
        'name' => 'Sucursal Norte',
    ]);

    $this->extraWorkspace = Workspace::factory()->create([
        'name' => 'Sucursal Este',
    ]);

    $this->user = User::factory()->create([
        'current_workspace_id' => $this->sourceWorkspace->id,
    ]);

    $this->user->workspaces()->attach([
        $this->sourceWorkspace->id,
        $this->targetWorkspace->id,
        $this->extraWorkspace->id,
    ]);
});

test('automations index exposes cloneable workspaces', function (): void {
    $response = $this->actingAs($this->user)->get(route('automations.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('automations/index')
        ->has('cloneableWorkspaces', 2)
        ->where('cloneableWorkspaces.0.name', 'Sucursal Este')
        ->where('cloneableWorkspaces.1.name', 'Sucursal Norte')
    );
});

test('user can clone an automation into another workspace', function (): void {
    $workflowId = (string) Str::uuid();
    $stageId = (string) Str::uuid();

    $automation = Automation::factory()->create([
        'workspace_id' => $this->sourceWorkspace->id,
        'name' => 'Recordatorio de entrega',
        'is_active' => true,
        'published_version' => 3,
    ]);

    AutomationVersion::factory()->create([
        'automation_id' => $automation->id,
        'version' => 3,
        'definition' => [
            'nodes' => [
                [
                    'id' => 'trigger-1',
                    'type' => 'workflow.stage_entered',
                    'config' => [
                        'workflow_id' => $workflowId,
                        'stage_id' => $stageId,
                    ],
                ],
                [
                    'id' => 'action-1',
                    'type' => 'telegram.send_message',
                    'config' => [
                        'message' => 'Tu orden esta lista.',
                    ],
                ],
            ],
            'edges' => [
                [
                    'from' => 'trigger-1',
                    'to' => 'action-1',
                ],
            ],
        ],
        'created_by' => $this->user->id,
    ]);

    AutomationTrigger::factory()->create([
        'automation_id' => $automation->id,
        'workspace_id' => $this->sourceWorkspace->id,
        'event_key' => 'workflow.job.stage_changed',
        'workflow_id' => $workflowId,
        'workflow_stage_id' => $stageId,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)->post(route('automations.clone', $automation), [
        'target_workspace_id' => $this->targetWorkspace->id,
    ]);

    $response->assertRedirect(route('automations.index'));
    $response->assertSessionHas('success');

    $clonedAutomation = Automation::query()
        ->withoutGlobalScope('workspace')
        ->where('workspace_id', $this->targetWorkspace->id)
        ->where('name', 'Recordatorio de entrega')
        ->first();

    expect($clonedAutomation)->not->toBeNull();
    expect($clonedAutomation->is_active)->toBeFalse();
    expect($clonedAutomation->published_version)->toBe(1);

    $clonedVersion = AutomationVersion::query()
        ->where('automation_id', $clonedAutomation->id)
        ->first();

    expect($clonedVersion)->not->toBeNull();
    expect($clonedVersion->version)->toBe(1);
    expect($clonedVersion->created_by)->toBe($this->user->id);
    expect(data_get($clonedVersion->definition, 'nodes.0.config.workflow_id'))->toBeNull();
    expect(data_get($clonedVersion->definition, 'nodes.0.config.stage_id'))->toBeNull();
    expect(data_get($clonedVersion->definition, 'nodes.1.config.message'))->toBe('Tu orden esta lista.');

    $clonedTrigger = AutomationTrigger::query()
        ->withoutGlobalScope('workspace')
        ->where('automation_id', $clonedAutomation->id)
        ->first();

    expect($clonedTrigger)->not->toBeNull();
    expect($clonedTrigger->workspace_id)->toBe($this->targetWorkspace->id);
    expect($clonedTrigger->workflow_id)->toBeNull();
    expect($clonedTrigger->workflow_stage_id)->toBeNull();
    expect($clonedTrigger->is_active)->toBeFalse();
});

test('user cannot clone an automation into the current workspace', function (): void {
    $automation = Automation::factory()->create([
        'workspace_id' => $this->sourceWorkspace->id,
    ]);

    $response = $this->actingAs($this->user)
        ->from(route('automations.index'))
        ->post(route('automations.clone', $automation), [
            'target_workspace_id' => $this->sourceWorkspace->id,
        ]);

    $response->assertRedirect(route('automations.index'));
    $response->assertSessionHasErrors('target_workspace_id');
});
