<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowStageAction
{
    /**
     * Execute the action.
     *
     * @param  array{name?: string, description?: string, color?: string, position?: int, is_active?: bool, is_initial?: bool, is_final?: bool}  $data
     */
    public function handle(WorkflowStage $stage, array $data): WorkflowStage
    {
        return DB::transaction(function () use ($stage, $data): WorkflowStage {
            $stage->update([
                'name' => $data['name'] ?? $stage->name,
                'description' => $data['description'] ?? $stage->description,
                'color' => $data['color'] ?? $stage->color,
                'position' => $data['position'] ?? $stage->position,
                'is_active' => $data['is_active'] ?? $stage->is_active,
                'is_initial' => $data['is_initial'] ?? $stage->is_initial,
                'is_final' => $data['is_final'] ?? $stage->is_final,
            ]);

            return $stage->fresh();
        });
    }
}
