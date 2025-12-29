import { GripVertical, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Mastertable, WorkflowField, WorkflowFieldType } from '@/types';

interface WorkflowFieldFormData {
    id?: string;
    name: string;
    key: string;
    type: WorkflowFieldType;
    mastertable_id: number | null;
    is_required: boolean;
    placeholder: string;
    default_value: string;
    position: number;
    _destroy?: boolean;
}

interface Props {
    fields: WorkflowField[];
    mastertables: Mastertable[];
    onChange: (fields: WorkflowFieldFormData[]) => void;
}

const fieldTypes: { value: WorkflowFieldType; label: string }[] = [
    { value: 'text', label: 'Texto' },
    { value: 'textarea', label: 'Texto largo' },
    { value: 'number', label: 'Número' },
    { value: 'date', label: 'Fecha' },
    { value: 'select', label: 'Selección' },
];

const generateKey = (name: string): string => {
    return name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '');
};

export function WorkflowFieldsEditor({ fields, mastertables, onChange }: Props) {
    const [localFields, setLocalFields] = useState<WorkflowFieldFormData[]>(
        fields.map((f, index) => ({
            id: f.id,
            name: f.name,
            key: f.key,
            type: f.type,
            mastertable_id: f.mastertable_id ?? null,
            is_required: f.is_required,
            placeholder: f.placeholder ?? '',
            default_value: f.default_value ?? '',
            position: f.position ?? index,
        })),
    );

    const updateFields = (newFields: WorkflowFieldFormData[]) => {
        setLocalFields(newFields);
        onChange(newFields);
    };

    const addField = () => {
        const newField: WorkflowFieldFormData = {
            name: '',
            key: '',
            type: 'text',
            mastertable_id: null,
            is_required: false,
            placeholder: '',
            default_value: '',
            position: localFields.length,
        };
        updateFields([...localFields, newField]);
    };

    const updateField = (index: number, updates: Partial<WorkflowFieldFormData>) => {
        const newFields = [...localFields];
        newFields[index] = { ...newFields[index], ...updates };

        // Auto-generate key from name if name changed and key is empty or matches old auto-generated key
        if (updates.name !== undefined) {
            const oldKey = generateKey(localFields[index].name);
            if (!newFields[index].key || newFields[index].key === oldKey) {
                newFields[index].key = generateKey(updates.name);
            }
        }

        updateFields(newFields);
    };

    const removeField = (index: number) => {
        const field = localFields[index];
        if (field.id) {
            // Mark for deletion instead of removing
            updateField(index, { _destroy: true });
        } else {
            // Remove from array if it's a new field
            const newFields = localFields.filter((_, i) => i !== index);
            updateFields(newFields);
        }
    };

    const visibleFields = localFields.filter((f) => !f._destroy);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">Campos personalizados</CardTitle>
                <CardDescription>Define campos adicionales que se mostrarán al crear tareas en este flujo de trabajo.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {visibleFields.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-6 text-center">
                        <p className="text-sm text-muted-foreground">No hay campos personalizados definidos.</p>
                        <Button variant="outline" size="sm" className="mt-3" onClick={addField}>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar campo
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="space-y-3">
                            {localFields.map((field, index) => {
                                if (field._destroy) return null;

                                return (
                                    <div key={field.id ?? `new-${index}`} className="rounded-lg border bg-gray-50/50 p-4">
                                        <div className="mb-3 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <GripVertical className="h-4 w-4 cursor-grab text-gray-400" />
                                                <span className="text-sm font-medium text-gray-700">Campo {visibleFields.indexOf(field) + 1}</span>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-gray-400 hover:text-red-500"
                                                onClick={() => removeField(index)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label>Nombre</Label>
                                                <Input
                                                    value={field.name}
                                                    onChange={(e) => updateField(index, { name: e.target.value })}
                                                    placeholder="Ej: Tipo de lente"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Clave</Label>
                                                <Input
                                                    value={field.key}
                                                    onChange={(e) => updateField(index, { key: e.target.value })}
                                                    placeholder="tipo_lente"
                                                    className="font-mono text-sm"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Tipo de campo</Label>
                                                <Select
                                                    value={field.type}
                                                    onValueChange={(value: WorkflowFieldType) => updateField(index, { type: value })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {fieldTypes.map((type) => (
                                                            <SelectItem key={type.value} value={type.value}>
                                                                {type.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            {field.type === 'select' && (
                                                <div className="space-y-2">
                                                    <Label>Tabla maestra</Label>
                                                    <Select
                                                        value={field.mastertable_id?.toString() ?? ''}
                                                        onValueChange={(value) =>
                                                            updateField(index, { mastertable_id: value ? parseInt(value) : null })
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Seleccionar tabla..." />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {mastertables.map((table) => (
                                                                <SelectItem key={table.id} value={table.id.toString()}>
                                                                    {table.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            )}

                                            <div className="space-y-2">
                                                <Label>Placeholder</Label>
                                                <Input
                                                    value={field.placeholder}
                                                    onChange={(e) => updateField(index, { placeholder: e.target.value })}
                                                    placeholder="Texto de ayuda..."
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label>Valor por defecto</Label>
                                                <Input
                                                    value={field.default_value}
                                                    onChange={(e) => updateField(index, { default_value: e.target.value })}
                                                    placeholder="Valor inicial..."
                                                />
                                            </div>
                                        </div>

                                        <div className="mt-4 flex items-center gap-2">
                                            <Checkbox
                                                id={`required-${index}`}
                                                checked={field.is_required}
                                                onCheckedChange={(checked) => updateField(index, { is_required: checked === true })}
                                            />
                                            <Label htmlFor={`required-${index}`} className="cursor-pointer text-sm">
                                                Campo obligatorio
                                            </Label>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>

                        <Button type="button" variant="outline" size="sm" onClick={addField}>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar otro campo
                        </Button>
                    </>
                )}

                {/* Hidden inputs to send fields data */}
                {localFields.map((field, index) => (
                    <div key={`hidden-${index}`}>
                        {field.id && <input type="hidden" name={`fields[${index}][id]`} value={field.id} />}
                        <input type="hidden" name={`fields[${index}][name]`} value={field.name} />
                        <input type="hidden" name={`fields[${index}][key]`} value={field.key} />
                        <input type="hidden" name={`fields[${index}][type]`} value={field.type} />
                        <input type="hidden" name={`fields[${index}][mastertable_id]`} value={field.mastertable_id ?? ''} />
                        <input type="hidden" name={`fields[${index}][is_required]`} value={field.is_required ? '1' : '0'} />
                        <input type="hidden" name={`fields[${index}][placeholder]`} value={field.placeholder} />
                        <input type="hidden" name={`fields[${index}][default_value]`} value={field.default_value} />
                        <input type="hidden" name={`fields[${index}][position]`} value={index.toString()} />
                        {field._destroy && <input type="hidden" name={`fields[${index}][_destroy]`} value="1" />}
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
