<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowAction
{
    /**
     * Execute the action.
     *
     * @param  array{name?: string, is_active?: bool}  $data
     */
    public function handle(Workflow $workflow, array $data): Workflow
    {
        return DB::transaction(function () use ($workflow, $data): Workflow {
            $workflow->update([
                'name' => $data['name'] ?? $workflow->name,
                'is_active' => $data['is_active'] ?? $workflow->is_active,
            ]);

            return $workflow->fresh();
        });
    }
}
