import type { WorkflowField } from '@/types';

const LAB_FIELD_KEYS = new Set(['laboratorio', 'laboratorios']);

export function canViewWorkflowField(field: WorkflowField, canViewLabs: boolean): boolean {
    return !LAB_FIELD_KEYS.has(field.key.toLowerCase()) || canViewLabs;
}
