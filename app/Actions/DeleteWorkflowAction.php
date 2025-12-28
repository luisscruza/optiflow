<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use Illuminate\Support\Facades\DB;

final readonly class DeleteWorkflowAction
{
    /**
     * Execute the action.
     */
    public function handle(Workflow $workflow): bool
    {
        return DB::transaction(function () use ($workflow): bool {
            foreach ($workflow->stages as $stage) {
                $stage->jobs()->delete();
            }

            $workflow->stages()->delete();

            return (bool) $workflow->delete();
        });
    }
}
