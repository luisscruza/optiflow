<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkflowStageAction
{
    /**
     * Execute the action.
     *
     * @param  array{name: string, description?: string, color?: string, position?: int, is_active?: bool, is_initial?: bool, is_final?: bool}  $data
     */
    public function handle(Workflow $workflow, array $data): WorkflowStage
    {
        return DB::transaction(function () use ($workflow, $data): WorkflowStage {
            return WorkflowStage::query()->create([
                'workflow_id' => $workflow->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#FFFFFF',
                'position' => $data['position'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'is_initial' => $data['is_initial'] ?? false,
                'is_final' => $data['is_final'] ?? false,
            ]);
        });
    }
}
