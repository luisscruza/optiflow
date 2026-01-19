import { useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type NodeType = 'http.webhook';

type WorkflowStageOption = { id: string; name: string };

type WorkflowOption = {
    id: string;
    name: string;
    stages: WorkflowStageOption[];
};

type WebhookAction = {
    type: NodeType;
    config: {
        url: string;
        method?: 'POST' | 'PUT' | 'PATCH' | 'DELETE';
        headers?: Record<string, string>;
        body?: any;
    };
};

type AutomationFormData = {
    name: string;
    is_active: boolean;
    trigger_workflow_id: string;
    trigger_stage_id: string;
    actions: WebhookAction[];
};

interface Props {
    mode: 'create' | 'edit';
    action: string;
    method: 'post' | 'patch';
    workflows: WorkflowOption[];
    templateVariables?: Array<{ label: string; token: string; description: string }>;
    initialValues?: {
        name: string;
        is_active: boolean;
        trigger_workflow_id: string;
        trigger_stage_id: string;
        actions: WebhookAction[];
    };
}

function safeJsonParse(value: string): unknown {
    if (!value.trim()) return undefined;
    return JSON.parse(value);
}

function safeJsonStringify(value: unknown): string {
    if (value === undefined || value === null) return '';
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return '';
    }
}

export function AutomationForm({ mode, action, method, workflows, templateVariables, initialValues }: Props) {
    const defaults = initialValues ?? {
        name: '',
        is_active: true,
        trigger_workflow_id: workflows[0]?.id ?? '',
        trigger_stage_id: workflows[0]?.stages?.[0]?.id ?? '',
        actions: [
            {
                type: 'http.webhook',
                config: { url: '', method: 'POST' },
            },
        ],
    };

    const form = useForm<AutomationFormData>({
        name: defaults.name,
        is_active: defaults.is_active,
        trigger_workflow_id: defaults.trigger_workflow_id,
        trigger_stage_id: defaults.trigger_stage_id,
        actions: defaults.actions,
    });

    const selectedWorkflow = useMemo(() => {
        return workflows.find((w) => w.id === form.data.trigger_workflow_id) ?? null;
    }, [workflows, form.data.trigger_workflow_id]);

    useEffect(() => {
        if (!selectedWorkflow) return;
        const stageExists = selectedWorkflow.stages.some((s) => s.id === form.data.trigger_stage_id);
        if (!stageExists) {
            form.setData('trigger_stage_id', selectedWorkflow.stages[0]?.id ?? '');
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedWorkflow]);

    const [headersText, setHeadersText] = useState<string>(() => safeJsonStringify(form.data.actions[0]?.config.headers ?? undefined));
    const [bodyText, setBodyText] = useState<string>(() => safeJsonStringify(form.data.actions[0]?.config.body ?? undefined));

    const [clientErrors, setClientErrors] = useState<{ headers?: string; body?: string }>({});

    const submit = () => {
        // Only supports webhook actions for now; keep it node-based but minimal.
        let parsedHeaders: Record<string, string> | undefined;
        let parsedBody: any;

        try {
            parsedHeaders = headersText ? (safeJsonParse(headersText) as Record<string, string>) : undefined;
        } catch {
            setClientErrors((prev) => ({ ...prev, headers: 'Headers debe ser JSON válido.' }));
            return;
        }

        try {
            parsedBody = bodyText ? safeJsonParse(bodyText) : undefined;
        } catch {
            setClientErrors((prev) => ({ ...prev, body: 'Body debe ser JSON válido.' }));
            return;
        }

        setClientErrors({});

        const actions = form.data.actions.map((a) => ({
            ...a,
            config: {
                ...a.config,
                headers: parsedHeaders,
                body: parsedBody,
            },
        }));

        form.transform((data) => ({ ...data, actions }));

        if (method === 'post') {
            form.post(action, {
                onFinish: () => form.transform((d) => d),
            });
        } else {
            form.patch(action, {
                onFinish: () => form.transform((d) => d),
            });
        }
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>General</CardTitle>
                    <CardDescription>Nombre y estado de la automatización.</CardDescription>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Ej: Notificar al entrar a Completado"
                        />
                        {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
                    </div>

                    <div className="flex items-center gap-3 pt-7">
                        <Checkbox checked={form.data.is_active} onCheckedChange={(checked) => form.setData('is_active', Boolean(checked))} />
                        <div>
                            <p className="text-sm font-medium">Activa</p>
                            <p className="text-xs text-muted-foreground">Si está inactiva, no se ejecutará.</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Disparador (Trigger)</CardTitle>
                    <CardDescription>Cuando una tarea entra a una etapa.</CardDescription>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label>Flujo de trabajo</Label>
                        <Select value={form.data.trigger_workflow_id} onValueChange={(v) => form.setData('trigger_workflow_id', v)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar" />
                            </SelectTrigger>
                            <SelectContent>
                                {workflows.map((w) => (
                                    <SelectItem key={w.id} value={w.id}>
                                        {w.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {form.errors.trigger_workflow_id && <p className="text-sm text-destructive">{form.errors.trigger_workflow_id}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label>Etapa</Label>
                        <Select value={form.data.trigger_stage_id} onValueChange={(v) => form.setData('trigger_stage_id', v)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar" />
                            </SelectTrigger>
                            <SelectContent>
                                {(selectedWorkflow?.stages ?? []).map((s) => (
                                    <SelectItem key={s.id} value={s.id}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {form.errors.trigger_stage_id && <p className="text-sm text-destructive">{form.errors.trigger_stage_id}</p>}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Nodos (Acciones)</CardTitle>
                    <CardDescription>Se ejecutan en orden después del trigger.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div className="rounded-md border bg-card p-3">
                            <div className="text-sm font-medium">Trigger</div>
                            <div className="mt-1 text-xs text-muted-foreground">workflow.stage_entered</div>
                        </div>
                        <div className="flex items-center justify-center text-muted-foreground">→</div>
                        <div className="rounded-md border bg-card p-3">
                            <div className="text-sm font-medium">Acción</div>
                            <div className="mt-1 text-xs text-muted-foreground">http.webhook</div>
                        </div>
                    </div>

                    <div className="rounded-md border p-4">
                        <p className="text-sm font-medium">Webhook HTTP</p>
                        <p className="text-xs text-muted-foreground">
                            Tipo de nodo: <span className="font-mono">http.webhook</span>
                        </p>

                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>URL</Label>
                                <Input
                                    value={form.data.actions[0]?.config.url ?? ''}
                                    onChange={(e) => {
                                        const actions = [...form.data.actions];
                                        actions[0] = {
                                            ...actions[0],
                                            config: { ...actions[0].config, url: e.target.value },
                                        };
                                        form.setData('actions', actions);
                                    }}
                                    placeholder="https://example.com/webhook"
                                />
                                {(form.errors as any)['actions.0.config.url'] && (
                                    <p className="text-sm text-destructive">{(form.errors as any)['actions.0.config.url']}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Método</Label>
                                <Select
                                    value={(form.data.actions[0]?.config.method ?? 'POST') as string}
                                    onValueChange={(v) => {
                                        const actions = [...form.data.actions];
                                        actions[0] = {
                                            ...actions[0],
                                            config: { ...actions[0].config, method: v as WebhookAction['config']['method'] },
                                        };
                                        form.setData('actions', actions);
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {['POST', 'PUT', 'PATCH', 'DELETE'].map((m) => (
                                            <SelectItem key={m} value={m}>
                                                {m}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Headers (JSON)</Label>
                                <Textarea
                                    value={headersText}
                                    onChange={(e) => setHeadersText(e.target.value)}
                                    placeholder='{"Authorization":"Bearer ..."}'
                                    className="min-h-[140px] font-mono text-sm"
                                />
                                {clientErrors.headers && <p className="text-sm text-destructive">{clientErrors.headers}</p>}
                                {(form.errors as any)['actions.0.config.headers'] && (
                                    <p className="text-sm text-destructive">{(form.errors as any)['actions.0.config.headers']}</p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Opcional. Usa plantillas como <span className="font-mono">{'{{job.id}}'}</span>.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label>Body (JSON)</Label>
                                <Textarea
                                    value={bodyText}
                                    onChange={(e) => setBodyText(e.target.value)}
                                    placeholder='{"job_id":"{{job.id}}"}'
                                    className="min-h-[140px] font-mono text-sm"
                                />
                                {clientErrors.body && <p className="text-sm text-destructive">{clientErrors.body}</p>}
                                {(form.errors as any)['actions.0.config.body'] && (
                                    <p className="text-sm text-destructive">{(form.errors as any)['actions.0.config.body']}</p>
                                )}
                                <p className="text-xs text-muted-foreground">Opcional. Se enviará como JSON.</p>
                            </div>
                        </div>

                        {Array.isArray(templateVariables) && templateVariables.length > 0 && (
                            <div className="mt-4 rounded-md bg-muted/50 p-3">
                                <p className="text-sm font-medium">Variables disponibles</p>
                                <p className="mt-1 text-xs text-muted-foreground">Puedes pegarlas directo en Headers/Body.</p>
                                <div className="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                                    {templateVariables.map((v) => (
                                        <div key={v.token} className="rounded border bg-background p-2">
                                            <div className="text-xs font-medium">{v.label}</div>
                                            <div className="mt-1 font-mono text-xs text-muted-foreground">{v.token}</div>
                                            <div className="mt-1 text-xs text-muted-foreground">{v.description}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" onClick={submit} disabled={form.processing}>
                            {mode === 'create' ? 'Crear automatización' : 'Guardar cambios'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
