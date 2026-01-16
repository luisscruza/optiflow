import { Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { AutomationNode } from './automation-builder';

type WorkflowOption = {
    id: string;
    name: string;
    stages: { id: string; name: string }[];
};

type TemplateVariable = {
    label: string;
    token: string;
    description: string;
};

type TelegramBotOption = {
    id: string;
    name: string;
    bot_username: string | null;
    default_chat_id: string | null;
};

interface NodeInspectorProps {
    node: AutomationNode | null;
    workflows: WorkflowOption[];
    templateVariables: TemplateVariable[];
    telegramBots?: TelegramBotOption[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
    onDelete: (nodeId: string) => void;
}

export function NodeInspector({ node, workflows, templateVariables, telegramBots = [], onUpdateConfig, onDelete }: NodeInspectorProps) {
    if (!node) {
        return (
            <div className="w-80 rounded-lg border bg-card p-4">
                <p className="text-center text-sm text-muted-foreground">Selecciona un nodo para configurarlo</p>

                <div className="mt-6">
                    <h4 className="mb-3 text-sm font-medium">Variables disponibles</h4>
                    <div className="max-h-[500px] space-y-2 overflow-y-auto">
                        {templateVariables.map((v) => (
                            <div key={v.token} className="rounded border bg-muted/50 p-2">
                                <div className="text-xs font-medium">{v.label}</div>
                                <div className="mt-0.5 font-mono text-xs text-primary">{v.token}</div>
                                <div className="mt-0.5 text-xs text-muted-foreground">{v.description}</div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    const isTrigger = node.data.nodeType === 'workflow.stage_entered';
    const isWebhook = node.data.nodeType === 'http.webhook';
    const isTelegram = node.data.nodeType === 'telegram.send_message';
    const isCondition = node.data.nodeType === 'logic.condition';

    return (
        <div className="w-80 overflow-y-auto rounded-lg border bg-card">
            <div className="flex items-center justify-between border-b p-3">
                <h3 className="font-semibold">{node.data.label}</h3>
                <span className="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">{node.data.nodeType}</span>
            </div>

            <div className="space-y-4 p-4">
                {isTrigger && <TriggerConfig node={node} workflows={workflows} onUpdateConfig={onUpdateConfig} />}

                {isWebhook && <WebhookConfig node={node} templateVariables={templateVariables} onUpdateConfig={onUpdateConfig} />}

                {isTelegram && (
                    <TelegramConfig node={node} templateVariables={templateVariables} telegramBots={telegramBots} onUpdateConfig={onUpdateConfig} />
                )}

                {isCondition && <ConditionConfig node={node} templateVariables={templateVariables} onUpdateConfig={onUpdateConfig} />}

                {!isTrigger && (
                    <Button variant="destructive" size="sm" className="w-full" onClick={() => onDelete(node.id)}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Eliminar nodo
                    </Button>
                )}
            </div>
        </div>
    );
}

function TriggerConfig({
    node,
    workflows,
    onUpdateConfig,
}: {
    node: AutomationNode;
    workflows: WorkflowOption[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
}) {
    const selectedWorkflow = useMemo(() => {
        return workflows.find((w) => w.id === node.data.config.workflow_id);
    }, [workflows, node.data.config.workflow_id]);

    return (
        <>
            <div className="space-y-2">
                <Label>Flujo de trabajo</Label>
                <Select
                    value={String(node.data.config.workflow_id || '')}
                    onValueChange={(v) => {
                        onUpdateConfig(node.id, { workflow_id: v, stage_id: '' });
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar flujo" />
                    </SelectTrigger>
                    <SelectContent>
                        {workflows.map((w) => (
                            <SelectItem key={w.id} value={w.id}>
                                {w.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Etapa (cuando entra a...)</Label>
                <Select
                    value={String(node.data.config.stage_id || '')}
                    onValueChange={(v) => onUpdateConfig(node.id, { stage_id: v })}
                    disabled={!selectedWorkflow}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar etapa" />
                    </SelectTrigger>
                    <SelectContent>
                        {(selectedWorkflow?.stages ?? []).map((s) => (
                            <SelectItem key={s.id} value={s.id}>
                                {s.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </>
    );
}

function WebhookConfig({
    node,
    templateVariables,
    onUpdateConfig,
}: {
    node: AutomationNode;
    templateVariables: TemplateVariable[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
}) {
    const [headersText, setHeadersText] = useState(() => {
        const h = node.data.config.headers;
        return h && typeof h === 'object' ? JSON.stringify(h, null, 2) : '';
    });

    const [bodyText, setBodyText] = useState(() => {
        const b = node.data.config.body;
        return b && typeof b === 'object' ? JSON.stringify(b, null, 2) : '';
    });

    const [jsonErrors, setJsonErrors] = useState<{ headers?: string; body?: string }>({});

    const updateHeaders = (text: string) => {
        setHeadersText(text);
        if (!text.trim()) {
            setJsonErrors((e) => ({ ...e, headers: undefined }));
            onUpdateConfig(node.id, { headers: {} });
            return;
        }
        try {
            const parsed = JSON.parse(text);
            setJsonErrors((e) => ({ ...e, headers: undefined }));
            onUpdateConfig(node.id, { headers: parsed });
        } catch {
            setJsonErrors((e) => ({ ...e, headers: 'JSON inválido' }));
        }
    };

    const updateBody = (text: string) => {
        setBodyText(text);
        if (!text.trim()) {
            setJsonErrors((e) => ({ ...e, body: undefined }));
            onUpdateConfig(node.id, { body: {} });
            return;
        }
        try {
            const parsed = JSON.parse(text);
            setJsonErrors((e) => ({ ...e, body: undefined }));
            onUpdateConfig(node.id, { body: parsed });
        } catch {
            setJsonErrors((e) => ({ ...e, body: 'JSON inválido' }));
        }
    };

    return (
        <>
            <div className="space-y-2">
                <Label>URL</Label>
                <Input
                    value={String(node.data.config.url || '')}
                    onChange={(e) => onUpdateConfig(node.id, { url: e.target.value })}
                    placeholder="https://api.example.com/webhook"
                />
            </div>

            <div className="space-y-2">
                <Label>Método HTTP</Label>
                <Select value={String(node.data.config.method || 'POST')} onValueChange={(v) => onUpdateConfig(node.id, { method: v })}>
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

            <div className="space-y-2">
                <Label>Headers (JSON)</Label>
                <Textarea
                    value={headersText}
                    onChange={(e) => updateHeaders(e.target.value)}
                    placeholder='{"Authorization": "Bearer ..."}'
                    className="min-h-[80px] font-mono text-xs"
                />
                {jsonErrors.headers && <p className="text-xs text-destructive">{jsonErrors.headers}</p>}
            </div>

            <div className="space-y-2">
                <Label>Body (JSON)</Label>
                <Textarea
                    value={bodyText}
                    onChange={(e) => updateBody(e.target.value)}
                    placeholder={'{\n  "contact": "{{contact.name}}"\n}'}
                    className="min-h-[120px] font-mono text-xs"
                />
                {jsonErrors.body && <p className="text-xs text-destructive">{jsonErrors.body}</p>}
            </div>

            <div className="rounded-md bg-muted/50 p-2">
                <p className="mb-2 text-xs font-medium">Variables disponibles</p>
                <div className="flex flex-wrap gap-1">
                    {templateVariables.slice(0, 6).map((v) => (
                        <span
                            key={v.token}
                            className="cursor-pointer rounded bg-background px-1.5 py-0.5 font-mono text-xs hover:bg-primary hover:text-primary-foreground"
                            onClick={() => {
                                navigator.clipboard.writeText(v.token);
                            }}
                            title={v.description}
                        >
                            {v.token}
                        </span>
                    ))}
                </div>
            </div>
        </>
    );
}

function TelegramConfig({
    node,
    templateVariables,
    telegramBots,
    onUpdateConfig,
}: {
    node: AutomationNode;
    templateVariables: TemplateVariable[];
    telegramBots: TelegramBotOption[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
}) {
    const selectedBot = telegramBots.find((b) => b.id === node.data.config.telegram_bot_id);
    const useManualConfig = !node.data.config.telegram_bot_id || node.data.config.telegram_bot_id === 'manual';

    const handleBotChange = (value: string) => {
        if (value === 'manual') {
            onUpdateConfig(node.id, { telegram_bot_id: null, bot_token: '' });
        } else {
            const bot = telegramBots.find((b) => b.id === value);
            onUpdateConfig(node.id, {
                telegram_bot_id: value,
                bot_token: '', // Clear manual token when using saved bot
                chat_id: bot?.default_chat_id || node.data.config.chat_id || '',
            });
        }
    };

    return (
        <>
            {telegramBots.length > 0 && (
                <div className="space-y-2">
                    <Label>Bot de Telegram</Label>
                    <Select value={(node.data.config.telegram_bot_id as string) || 'manual'} onValueChange={handleBotChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar bot" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="manual">⚙️ Configuración manual</SelectItem>
                            {telegramBots.map((bot) => (
                                <SelectItem key={bot.id} value={bot.id}>
                                    {bot.name} {bot.bot_username ? `(@${bot.bot_username})` : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {selectedBot && <p className="text-xs text-muted-foreground">Usando bot guardado: @{selectedBot.bot_username}</p>}
                </div>
            )}

            {(useManualConfig || telegramBots.length === 0) && (
                <>
                    <div className="rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950">
                        <p className="text-xs font-medium text-amber-800 dark:text-amber-200">
                            💡 Para obtener el Bot Token, habla con @BotFather en Telegram. Para el Chat ID, puedes usar @userinfobot.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label>Bot Token</Label>
                        <Input
                            type="password"
                            value={String(node.data.config.bot_token || '')}
                            onChange={(e) => onUpdateConfig(node.id, { bot_token: e.target.value })}
                            placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                        />
                        <p className="text-xs text-muted-foreground">El token de tu bot de Telegram</p>
                    </div>
                </>
            )}

            <div className="space-y-2">
                <Label>Chat ID</Label>
                <Input
                    value={String(node.data.config.chat_id || '')}
                    onChange={(e) => onUpdateConfig(node.id, { chat_id: e.target.value })}
                    placeholder="-1001234567890 o {{contact.telegram_id}}"
                />
                <p className="text-xs text-muted-foreground">ID del chat, grupo o canal. Puedes usar variables como {'{{contact.telegram_id}}'}</p>
            </div>

            <div className="space-y-2">
                <Label>Mensaje</Label>
                <Textarea
                    value={String(node.data.config.message || '')}
                    onChange={(e) => onUpdateConfig(node.id, { message: e.target.value })}
                    placeholder={'🔔 Nuevo evento\n\nContacto: {{contact.name}}\nTeléfono: {{contact.phone}}'}
                    className="min-h-[120px]"
                />
                <p className="text-xs text-muted-foreground">
                    Soporta HTML: &lt;b&gt;negrita&lt;/b&gt;, &lt;i&gt;itálica&lt;/i&gt;, &lt;code&gt;código&lt;/code&gt;
                </p>
            </div>

            <div className="space-y-2">
                <Label>Formato del mensaje</Label>
                <Select value={String(node.data.config.parse_mode || 'HTML')} onValueChange={(v) => onUpdateConfig(node.id, { parse_mode: v })}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="HTML">HTML</SelectItem>
                        <SelectItem value="Markdown">Markdown</SelectItem>
                        <SelectItem value="MarkdownV2">Markdown V2</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="disable_notification"
                    checked={Boolean(node.data.config.disable_notification)}
                    onChange={(e) => onUpdateConfig(node.id, { disable_notification: e.target.checked })}
                    className="h-4 w-4 rounded border-gray-300"
                />
                <Label htmlFor="disable_notification" className="cursor-pointer text-sm font-normal">
                    Enviar sin notificación (silencioso)
                </Label>
            </div>

            <div className="rounded-md bg-muted/50 p-2">
                <p className="mb-2 text-xs font-medium">Variables disponibles</p>
                <div className="flex flex-wrap gap-1">
                    {templateVariables.slice(0, 8).map((v) => (
                        <span
                            key={v.token}
                            className="cursor-pointer rounded bg-background px-1.5 py-0.5 font-mono text-xs hover:bg-primary hover:text-primary-foreground"
                            onClick={() => {
                                navigator.clipboard.writeText(v.token);
                            }}
                            title={v.description}
                        >
                            {v.token}
                        </span>
                    ))}
                </div>
            </div>
        </>
    );
}

const CONDITION_OPERATORS = [
    { value: 'equals', label: 'Es igual a' },
    { value: 'not_equals', label: 'No es igual a' },
    { value: 'contains', label: 'Contiene' },
    { value: 'not_contains', label: 'No contiene' },
    { value: 'starts_with', label: 'Empieza con' },
    { value: 'ends_with', label: 'Termina con' },
    { value: 'greater_than', label: 'Mayor que' },
    { value: 'less_than', label: 'Menor que' },
    { value: 'greater_or_equal', label: 'Mayor o igual' },
    { value: 'less_or_equal', label: 'Menor o igual' },
    { value: 'is_empty', label: 'Está vacío' },
    { value: 'is_not_empty', label: 'No está vacío' },
    { value: 'is_null', label: 'Es nulo' },
    { value: 'is_not_null', label: 'No es nulo' },
    { value: 'in_list', label: 'Está en lista' },
    { value: 'regex', label: 'Coincide con regex' },
];

function ConditionConfig({
    node,
    templateVariables,
    onUpdateConfig,
}: {
    node: AutomationNode;
    templateVariables: TemplateVariable[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
}) {
    const operatorNeedsValue = !['is_empty', 'is_not_empty', 'is_null', 'is_not_null'].includes(String(node.data.config.operator || 'equals'));

    return (
        <>
            <div className="rounded-md border border-purple-200 bg-purple-50 p-3 dark:border-purple-800 dark:bg-purple-950">
                <p className="text-xs font-medium text-purple-800 dark:text-purple-200">
                    🔀 Configura la condición. El nodo tiene dos salidas: "Sí" (verde) si la condición se cumple, "No" (rojo) si no.
                </p>
            </div>

            <div className="space-y-2">
                <Label>Campo a evaluar</Label>
                <Select value={String(node.data.config.field || '')} onValueChange={(v) => onUpdateConfig(node.id, { field: v })}>
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar campo" />
                    </SelectTrigger>
                    <SelectContent>
                        {templateVariables.map((v) => (
                            <SelectItem key={v.token} value={v.token}>
                                {v.label} ({v.token})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">El campo cuyo valor será evaluado</p>
            </div>

            <div className="space-y-2">
                <Label>Operador</Label>
                <Select value={String(node.data.config.operator || 'equals')} onValueChange={(v) => onUpdateConfig(node.id, { operator: v })}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {CONDITION_OPERATORS.map((op) => (
                            <SelectItem key={op.value} value={op.value}>
                                {op.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {operatorNeedsValue && (
                <div className="space-y-2">
                    <Label>Valor a comparar</Label>
                    <Input
                        value={String(node.data.config.value || '')}
                        onChange={(e) => onUpdateConfig(node.id, { value: e.target.value })}
                        placeholder="Valor esperado"
                    />
                    <p className="text-xs text-muted-foreground">Para "Está en lista", separa los valores con comas</p>
                </div>
            )}

            <div className="rounded-md bg-muted/50 p-2">
                <p className="mb-2 text-xs font-medium">Campos disponibles</p>
                <div className="flex flex-wrap gap-1">
                    {templateVariables.slice(0, 8).map((v) => (
                        <span
                            key={v.token}
                            className="cursor-pointer rounded bg-background px-1.5 py-0.5 font-mono text-xs hover:bg-primary hover:text-primary-foreground"
                            onClick={() => onUpdateConfig(node.id, { field: v.token })}
                            title={v.description}
                        >
                            {v.token}
                        </span>
                    ))}
                </div>
            </div>
        </>
    );
}
