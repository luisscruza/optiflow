<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkflowAction
{
    public function __construct(private CreateWorkflowStageAction $createWorkflowStageAction)
    {
        //
    }

    /**
     * Execute the action.
     */
    public function handle(array $data): Workflow
    {
        return DB::transaction(function () use ($data): Workflow {
            $workflow = Workflow::query()->create([
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->createInitialStages($workflow);

            return $workflow;
        });
    }

    private function createInitialStages(Workflow $workflow): void
    {
        $stages = [
            [
                'name' => 'To Do',
                'description' => 'Tareas por hacer',
                'color' => '#FF0000',
                'position' => 1,
                'is_initial' => true,
            ],
            [
                'name' => 'Completado',
                'description' => 'Tareas completadas',
                'color' => '#00FF00',
                'position' => 2,
                'is_final' => true,
            ],
        ];

        foreach ($stages as $stage) {
            $this->createWorkflowStageAction->handle($workflow, $stage);
        }
    }
}
