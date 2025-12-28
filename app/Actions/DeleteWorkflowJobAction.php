<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WorkflowJob;
use Illuminate\Support\Facades\DB;

final readonly class DeleteWorkflowJobAction
{
    /**
     * Execute the action.
     */
    public function handle(WorkflowJob $job): bool
    {
        return DB::transaction(function () use ($job): bool {
            return (bool) $job->delete();
        });
    }
}
