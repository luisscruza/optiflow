<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowStage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class DeleteWorkflowStageAction
{
    /**
     * Execute the action.
     */
    public function handle(WorkflowStage $stage): bool
    {
        return DB::transaction(function () use ($stage): bool {
            if ($stage->jobs()->exists()) {
                throw new RuntimeException('Cannot delete stage with existing jobs. Move or delete jobs first.');
            }

            return (bool) $stage->delete();
        });
    }
}
