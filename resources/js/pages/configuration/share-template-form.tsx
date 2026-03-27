import { useForm } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type ShareTemplate, type ShareTemplateChannel, type ShareTemplateEntity, type ShareVariable } from '@/types';

interface ShareTemplateFormProps {
    action: string;
    method: 'post' | 'put';
    entityOptions: Record<string, string>;
    channelOptions: Record<string, string>;
    variableGroups: Record<string, ShareVariable[]>;
    template?: ShareTemplate;
}

export default function ShareTemplateForm({ action, method, entityOptions, channelOptions, variableGroups, template }: ShareTemplateFormProps) {
    const form = useForm({
        entity_type: (template?.entity_type ?? 'invoice') as ShareTemplateEntity,
        channel: (template?.channel ?? 'email') as ShareTemplateChannel,
        name: template?.name ?? '',
        subject: template?.subject ?? '',
        body: template?.body ?? '',
        is_active: template?.is_active ?? true,
    });

    const currentVariables = variableGroups[form.data.entity_type] ?? [];

    const submit = () => {
        if (method === 'post') {
            form.post(action);
            return;
        }

        form.transform((data) => ({ ...data, _method: 'PUT' }));
        form.post(action);
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Configuracion de la plantilla</CardTitle>
                    <CardDescription>Define el canal, la entidad y el contenido base que se usara al compartir.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="entity_type">Entidad</Label>
                            <select
                                id="entity_type"
                                value={form.data.entity_type}
                                onChange={(event) => form.setData('entity_type', event.target.value as ShareTemplateEntity)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                            >
                                {Object.entries(entityOptions).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.entity_type && <p className="text-sm text-red-600">{form.errors.entity_type}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="channel">Canal</Label>
                            <select
                                id="channel"
                                value={form.data.channel}
                                onChange={(event) => form.setData('channel', event.target.value as ShareTemplateChannel)}
                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                            >
                                {Object.entries(channelOptions).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                            {form.errors.channel && <p className="text-sm text-red-600">{form.errors.channel}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre</Label>
                        <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                        {form.errors.name && <p className="text-sm text-red-600">{form.errors.name}</p>}
                    </div>

                    {form.data.channel === 'email' && (
                        <div className="space-y-2">
                            <Label htmlFor="subject">Asunto</Label>
                            <Input id="subject" value={form.data.subject} onChange={(event) => form.setData('subject', event.target.value)} />
                            {form.errors.subject && <p className="text-sm text-red-600">{form.errors.subject}</p>}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="body">Contenido</Label>
                        <Textarea id="body" value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} rows={12} />
                        {form.errors.body && <p className="text-sm text-red-600">{form.errors.body}</p>}
                    </div>

                    <label className="flex items-center gap-3 rounded-lg border p-4 text-sm">
                        <input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                        Plantilla activa
                    </label>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Variables disponibles</CardTitle>
                    <CardDescription>Inserta estos tokens en la plantilla. Se reemplazan al abrir el modal de compartir.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {currentVariables.map((variable) => (
                        <button
                            key={variable.token}
                            type="button"
                            onClick={() => form.setData('body', `${form.data.body}${form.data.body === '' ? '' : ' '}${variable.token}`)}
                            className="flex w-full items-start justify-between rounded-lg border p-3 text-left transition hover:bg-gray-50"
                        >
                            <div>
                                <div className="font-medium text-gray-900">{variable.label}</div>
                                <div className="text-sm text-gray-600">{variable.description}</div>
                            </div>
                            <code className="rounded bg-gray-100 px-2 py-1 text-xs">{variable.token}</code>
                        </button>
                    ))}
                </CardContent>
            </Card>

            <div className="flex justify-end gap-3">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                    Cancelar
                </Button>
                <Button type="button" onClick={submit} disabled={form.processing}>
                    Guardar plantilla
                </Button>
            </div>
        </div>
    );
}
