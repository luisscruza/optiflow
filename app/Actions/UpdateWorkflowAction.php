<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Workflow;
use App\Models\WorkflowField;
use Illuminate\Support\Facades\DB;

final readonly class UpdateWorkflowAction
{
    /**
     * Execute the action.
     *
     * @param  array{name?: string, is_active?: bool, invoice_requirement?: string|null, prescription_requirement?: string|null, fields?: array<int, array{id?: string, name: string, key: string, type: string, mastertable_id?: int|null, is_required?: bool, placeholder?: string|null, default_value?: string|null, position?: int, _destroy?: bool}>}  $data
     */
    public function handle(Workflow $workflow, array $data): Workflow
    {
        return DB::transaction(function () use ($workflow, $data): Workflow {
            $workflow->update([
                'name' => $data['name'] ?? $workflow->name,
                'is_active' => $data['is_active'] ?? $workflow->is_active,
                'invoice_requirement' => array_key_exists('invoice_requirement', $data) ? $data['invoice_requirement'] : $workflow->invoice_requirement,
                'prescription_requirement' => array_key_exists('prescription_requirement', $data) ? $data['prescription_requirement'] : $workflow->prescription_requirement,
            ]);

            if (isset($data['fields'])) {
                $this->syncFields($workflow, $data['fields']);
            }

            return $workflow->fresh(['fields']);
        });
    }

    /**
     * Sync workflow fields.
     *
     * @param  array<int, array{id?: string, name: string, key: string, type: string, mastertable_id?: int|null, is_required?: bool, placeholder?: string|null, default_value?: string|null, position?: int, _destroy?: bool}>  $fieldsData
     */
    private function syncFields(Workflow $workflow, array $fieldsData): void
    {
        foreach ($fieldsData as $fieldData) {
            if (empty($fieldData['name'])) {
                continue;
            }

            $shouldDestroy = isset($fieldData['_destroy']) && ($fieldData['_destroy'] === true || $fieldData['_destroy'] === '1');

            if (isset($fieldData['id']) && ! empty($fieldData['id'])) {
                $field = WorkflowField::find($fieldData['id']);

                if ($field) {
                    if ($shouldDestroy) {
                        $field->delete();
                    } else {
                        $field->update([
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
            } else {
                if (! $shouldDestroy) {
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
    }
}
