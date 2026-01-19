<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use App\Models\WorkflowField;
use Illuminate\Support\Facades\DB;

final readonly class CreateWorkflowAction
{
    public function __construct(private CreateWorkflowStageAction $createWorkflowStageAction)
    {
        //
    }

    /**
     * Execute the action.
     *
     * @param  array{name: string, is_active?: bool, invoice_requirement?: string|null, prescription_requirement?: string|null, fields?: array<int, array{name: string, key: string, type: string, mastertable_id?: int|null, is_required?: bool, placeholder?: string|null, default_value?: string|null, position?: int}>}  $data
     */
    public function handle(array $data): Workflow
    {
        return DB::transaction(function () use ($data): Workflow {
            $workflow = Workflow::query()->create([
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? true,
                'invoice_requirement' => $data['invoice_requirement'] ?? null,
                'prescription_requirement' => $data['prescription_requirement'] ?? null,
            ]);

            $this->createInitialStages($workflow);

            if (isset($data['fields']) && is_array($data['fields'])) {
                $this->createFields($workflow, $data['fields']);
            }

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

    /**
     * Create workflow fields.
     *
     * @param  array<int, array{name: string, key: string, type: string, mastertable_id?: int|null, is_required?: bool, placeholder?: string|null, default_value?: string|null, position?: int}>  $fieldsData
     */
    private function createFields(Workflow $workflow, array $fieldsData): void
    {
        foreach ($fieldsData as $fieldData) {
            if (empty($fieldData['name'])) {
                continue;
            }

            WorkflowField::create([
                'workflow_id' => $workflow->id,
                'name' => $fieldData['name'],
                'key' => $fieldData['key'],
                'type' => $fieldData['type'],
                'mastertable_id' => $fieldData['mastertable_id'] ?? null,
                'is_required' => $fieldData['is_required'] ?? false,
                'placeholder' => $fieldData['placeholder'] ?? null,
                'default_value' => $fieldData['default_value'] ?? null,
                'position' => $fieldData['position'] ?? 0,
            ]);
        }
    }
}
