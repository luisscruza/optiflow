<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WorkflowJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AutomationTestDataController
{
    /**
     * Get workflow jobs for test mode.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $workflowId = $request->input('workflow_id');

        if (! $workflowId) {
            return response()->json(['jobs' => []]);
        }

        $jobs = WorkflowJob::query()
            ->with(['contact', 'invoice', 'workflowStage', 'workflow'])
            ->where('workflow_id', $workflowId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (WorkflowJob $job): array => [
                'id' => $job->id,
                'title' => $job->contact?->name ?? $job->workflow?->name ?? (string) $job->id,
                'contact_name' => $job->contact?->name ?? '—',
                'stage_name' => $job->workflowStage?->name ?? '—',
                'created_at' => $job->created_at?->toDateTimeString(),
            ]);

        return response()->json(['jobs' => $jobs]);
    }
}
