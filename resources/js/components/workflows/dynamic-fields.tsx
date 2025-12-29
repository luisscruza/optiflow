import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { WorkflowField } from '@/types';

interface DynamicFieldsProps {
    fields: WorkflowField[];
    values: Record<string, string | number | null>;
    onChange: (key: string, value: string | number | null) => void;
}

export function DynamicFields({ fields, values, onChange }: DynamicFieldsProps) {
    if (fields.length === 0) return null;

    const renderField = (field: WorkflowField) => {
        const value = values[field.key] ?? field.default_value ?? '';

        switch (field.type) {
            case 'text':
                return (
                    <Input
                        id={`field-${field.key}`}
                        value={String(value)}
                        onChange={(e) => onChange(field.key, e.target.value || null)}
                        placeholder={field.placeholder ?? ''}
                    />
                );

            case 'textarea':
                return (
                    <Textarea
                        id={`field-${field.key}`}
                        value={String(value)}
                        onChange={(e) => onChange(field.key, e.target.value || null)}
                        placeholder={field.placeholder ?? ''}
                        rows={3}
                    />
                );

            case 'number':
                return (
                    <Input
                        id={`field-${field.key}`}
                        type="number"
                        value={value !== null && value !== '' ? String(value) : ''}
                        onChange={(e) => onChange(field.key, e.target.value ? parseFloat(e.target.value) : null)}
                        placeholder={field.placeholder ?? ''}
                    />
                );

            case 'date':
                return (
                    <Input
                        id={`field-${field.key}`}
                        type="date"
                        value={String(value)}
                        onChange={(e) => onChange(field.key, e.target.value || null)}
                    />
                );

            case 'select':
                return (
                    <Select value={String(value)} onValueChange={(v) => onChange(field.key, v || null)}>
                        <SelectTrigger>
                            <SelectValue placeholder={field.placeholder ?? 'Seleccionar...'} />
                        </SelectTrigger>
                        <SelectContent>
                            {field.mastertable?.items?.map((item) => (
                                <SelectItem key={item.id} value={item.name}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                );

            default:
                return null;
        }
    };

    return (
        <div className="space-y-4 border-t pt-4">
            <h4 className="text-sm font-medium text-muted-foreground">Campos adicionales</h4>
            {fields.map((field) => (
                <div key={field.id} className="space-y-2">
                    <Label htmlFor={`field-${field.key}`}>
                        {field.name}
                        {field.is_required && <span className="text-destructive"> *</span>}
                    </Label>
                    {renderField(field)}
                </div>
            ))}
        </div>
    );
}
