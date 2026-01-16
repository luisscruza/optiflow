<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use App\Models\Workspace;

it('creates an automation from visual builder payload (nodes/edges only)', function (): void {
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
        'name' => 'Stage A',
        'description' => null,
        'color' => '#FFFFFF',
        'position' => 1,
        'is_active' => true,
        'is_initial' => true,
        'is_final' => false,
    ]);

    $response = $this->post('/automations', [
        'name' => 'Builder Automation',
        'is_active' => true,
        'trigger_workflow_id' => $workflow->id,
        'trigger_stage_id' => $stage->id,
        'nodes' => [
            [
                'id' => 'trigger-1',
                'type' => 'workflow.stage_entered',
                'position' => ['x' => 100, 'y' => 200],
                'config' => [
                    'workflow_id' => $workflow->id,
                    'stage_id' => $stage->id,
                ],
            ],
            [
                'id' => 'cond-1',
                'type' => 'logic.condition',
                'position' => ['x' => 350, 'y' => 200],
                'config' => [
                    'field' => 'job.priority',
                    'operator' => 'equals',
                    'value' => 'high',
                ],
            ],
            [
                'id' => 'tg-1',
                'type' => 'telegram.send_message',
                'position' => ['x' => 600, 'y' => 150],
                'config' => [
                    'bot_token' => '123:ABC',
                    'chat_id' => '123456789',
                    'message' => 'Hello',
                    'parse_mode' => 'HTML',
                    'disable_notification' => false,
                ],
            ],
        ],
        'edges' => [
            [
                'from' => 'trigger-1',
                'to' => 'cond-1',
            ],
            [
                'from' => 'cond-1',
                'to' => 'tg-1',
                'sourceHandle' => 'true',
            ],
        ],
    ]);

    $response->assertRedirect('/automations');
});
