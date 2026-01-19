import { ChevronDown, ChevronRight, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { AutomationNode, WorkflowOption } from './automation-builder';
import { getGroupedOutputSchema, getNodeType, isTriggerType, type NodeTypeRegistry } from './registry';

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

type WhatsappAccountOption = {
    id: string;
    name: string;
    display_phone_number: string | null;
    business_account_id: string | null;
};

interface NodeInspectorProps {
    node: AutomationNode | null;
    workflows: WorkflowOption[];
    templateVariables: TemplateVariable[];
    telegramBots?: TelegramBotOption[];
    whatsappAccounts?: WhatsappAccountOption[];
    nodeTypeRegistry: NodeTypeRegistry;
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
    onUpdateNodeType: (nodeId: string, nodeType: string) => void;
    onDelete: (nodeId: string) => void;
}

export function NodeInspector({
    node,
    workflows,
    templateVariables,
    telegramBots = [],
    whatsappAccounts = [],
    nodeTypeRegistry,
    onUpdateConfig,
    onUpdateNodeType,
    onDelete,
}: NodeInspectorProps) {
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

    const nodeTypeDef = getNodeType(nodeTypeRegistry, node.data.nodeType);
    const isTrigger = isTriggerType(nodeTypeRegistry, node.data.nodeType);
    const inspectorComponent = nodeTypeDef?.inspectorComponent;

    return (
        <div className="w-80 overflow-y-auto rounded-lg border bg-card">
            <div className="flex items-center justify-between border-b p-3">
                <h3 className="font-semibold">{nodeTypeDef?.label ?? node.data.label}</h3>
                <span className="rounded bg-muted px-2 py-0.5 text-xs text-muted-foreground">{node.data.nodeType}</span>
            </div>

            <div className="space-y-4 p-4">
                {inspectorComponent === 'TriggerConfig' && (
                    <TriggerConfig
                        node={node}
                        workflows={workflows}
                        nodeTypeRegistry={nodeTypeRegistry}
                        onUpdateConfig={onUpdateConfig}
                        onUpdateNodeType={onUpdateNodeType}
                    />
                )}

                {inspectorComponent === 'WebhookConfig' && (
                    <WebhookConfig node={node} templateVariables={templateVariables} onUpdateConfig={onUpdateConfig} />
                )}

                {inspectorComponent === 'TelegramConfig' && (
                    <TelegramConfig node={node} templateVariables={templateVariables} telegramBots={telegramBots} onUpdateConfig={onUpdateConfig} />
                )}

                {inspectorComponent === 'WhatsappConfig' && (
                    <WhatsappConfig
                        node={node}
                        templateVariables={templateVariables}
                        whatsappAccounts={whatsappAccounts}
                        onUpdateConfig={onUpdateConfig}
                    />
                )}

                {inspectorComponent === 'ConditionConfig' && (
                    <ConditionConfig node={node} templateVariables={templateVariables} onUpdateConfig={onUpdateConfig} />
                )}

                {!isTrigger && (
                    <Button variant="destructive" size="sm" className="w-full" onClick={() => onDelete(node.id)}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Eliminar nodo
                    </Button>
                )}

                {/* Output Schema Panel */}
                <OutputSchemaPanel
                    nodeType={node.data.nodeType}
                    nodeTypeRegistry={nodeTypeRegistry}
                    workflows={workflows}
                    nodeConfig={node.data.config}
                />
            </div>
        </div>
    );
}

function TriggerConfig({
    node,
    workflows,
    nodeTypeRegistry,
    onUpdateConfig,
    onUpdateNodeType,
}: {
    node: AutomationNode;
    workflows: WorkflowOption[];
    nodeTypeRegistry: NodeTypeRegistry;
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
    onUpdateNodeType: (nodeId: string, nodeType: string) => void;
}) {
    const triggerType = node.data.nodeType;
    const isWorkflowTrigger = triggerType === 'workflow.stage_entered';

    // Get all trigger types from the registry
    const triggerOptions = useMemo(() => {
        return Object.values(nodeTypeRegistry.triggers).map((t) => ({
            value: t.key,
            label: t.label,
        }));
    }, [nodeTypeRegistry]);

    const selectedWorkflow = useMemo(() => {
        return workflows.find((w) => w.id === node.data.config.workflow_id);
    }, [workflows, node.data.config.workflow_id]);

    return (
        <>
            <div className="space-y-2">
                <Label>Tipo de disparador</Label>
                <Select
                    value={triggerType}
                    onValueChange={(v) => {
                        onUpdateNodeType(node.id, v);
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar tipo" />
                    </SelectTrigger>
                    <SelectContent>
                        {triggerOptions.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {!isWorkflowTrigger && (
                <div className="rounded border bg-muted/50 p-3 text-sm text-muted-foreground">
                    Este disparador se ejecuta automáticamente cuando ocurre el evento seleccionado en una factura.
                </div>
            )}

            {isWorkflowTrigger && (
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
            )}
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

function WhatsappConfig({
    node,
    templateVariables,
    whatsappAccounts,
    onUpdateConfig,
}: {
    node: AutomationNode;
    templateVariables: TemplateVariable[];
    whatsappAccounts: WhatsappAccountOption[];
    onUpdateConfig: (nodeId: string, config: Record<string, unknown>) => void;
}) {
    type TemplateParam = {
        name: string;
        type: string;
        component: string;
        example?: string;
        positional?: boolean;
        button_index?: number;
        button_text?: string;
    };

    type WhatsappTemplate = {
        name: string;
        language: string;
        category?: string;
        parameter_format?: string;
        parameters: TemplateParam[];
    };

    const [templates, setTemplates] = useState<WhatsappTemplate[]>([]);
    const [loadingTemplates, setLoadingTemplates] = useState(false);

    const selectedAccountId = String(node.data.config.whatsapp_account_id || '');
    const selectedAccount = whatsappAccounts.find((a) => a.id === selectedAccountId);
    const action = String(node.data.config.action || 'send_message');
    const selectedTemplateName = String(node.data.config.template_name || '');
    const selectedTemplate = templates.find((t) => t.name === selectedTemplateName);
    const templateParams = (node.data.config.template_params as Record<string, string>) || {};

    const fetchTemplates = async (accountId: string) => {
        if (!accountId) return;

        setLoadingTemplates(true);
        try {
            const response = await fetch(`/whatsapp-accounts/${accountId}/templates`);
            if (response.ok) {
                const data = await response.json();
                setTemplates(data.templates || []);
            }
        } catch {
            console.error('Failed to fetch templates');
        } finally {
            setLoadingTemplates(false);
        }
    };

    const handleAccountChange = (accountId: string) => {
        onUpdateConfig(node.id, { whatsapp_account_id: accountId });
        if (action === 'send_template') {
            fetchTemplates(accountId);
        }
    };

    const handleActionChange = (newAction: string) => {
        onUpdateConfig(node.id, { action: newAction });
        if (newAction === 'send_template' && selectedAccountId) {
            fetchTemplates(selectedAccountId);
        }
    };

    const handleTemplateSelect = (templateName: string) => {
        const template = templates.find((t) => t.name === templateName);
        onUpdateConfig(node.id, {
            template_name: templateName,
            template_language: template?.language || 'es',
            template_params: {}, // Reset params when template changes
        });
    };

    const handleParamChange = (paramName: string, value: string) => {
        const newParams = { ...templateParams, [paramName]: value };
        onUpdateConfig(node.id, { template_params: newParams });
    };

    return (
        <>
            <div className="rounded-md border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950">
                <p className="text-xs font-medium text-green-800 dark:text-green-200">
                    📱 Envía mensajes por WhatsApp Business Cloud API. Puedes usar variables de plantilla en los campos.
                </p>
            </div>

            <div className="space-y-2">
                <Label>Cuenta de WhatsApp</Label>
                <Select value={selectedAccountId} onValueChange={handleAccountChange}>
                    <SelectTrigger>
                        <SelectValue placeholder="Seleccionar cuenta" />
                    </SelectTrigger>
                    <SelectContent>
                        {whatsappAccounts.map((account) => (
                            <SelectItem key={account.id} value={account.id}>
                                {account.name} {account.display_phone_number && `(${account.display_phone_number})`}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {whatsappAccounts.length === 0 && (
                    <p className="text-xs text-amber-600 dark:text-amber-400">
                        No hay cuentas configuradas.{' '}
                        <a href="/whatsapp-accounts" className="underline">
                            Configura una cuenta
                        </a>
                    </p>
                )}
            </div>

            <div className="space-y-2">
                <Label>Acción</Label>
                <Select value={action} onValueChange={handleActionChange}>
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="send_message">Enviar mensaje</SelectItem>
                        <SelectItem value="send_template">Enviar plantilla</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div className="space-y-2">
                <Label>Número de destino (To)</Label>
                <Input
                    value={String(node.data.config.to || '')}
                    onChange={(e) => onUpdateConfig(node.id, { to: e.target.value })}
                    placeholder="{{contact.phone}} o +1234567890"
                />
                <p className="text-xs text-muted-foreground">Número con código de país, sin espacios ni signos</p>
            </div>

            {action === 'send_message' && (
                <>
                    <div className="space-y-2">
                        <Label>Mensaje</Label>
                        <Textarea
                            value={String(node.data.config.message || '')}
                            onChange={(e) => onUpdateConfig(node.id, { message: e.target.value })}
                            placeholder="Hola {{contact.name}}, tu cita está confirmada."
                            rows={4}
                        />
                    </div>

                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="preview_url"
                            checked={Boolean(node.data.config.preview_url)}
                            onChange={(e) => onUpdateConfig(node.id, { preview_url: e.target.checked })}
                            className="h-4 w-4"
                        />
                        <Label htmlFor="preview_url" className="cursor-pointer text-sm">
                            Mostrar vista previa de enlaces
                        </Label>
                    </div>
                </>
            )}

            {action === 'send_template' && (
                <>
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label>Plantilla</Label>
                            {selectedAccount?.business_account_id && (
                                <button
                                    type="button"
                                    onClick={() => fetchTemplates(selectedAccountId)}
                                    className="text-xs text-primary hover:underline"
                                    disabled={loadingTemplates}
                                >
                                    {loadingTemplates ? 'Cargando...' : 'Actualizar'}
                                </button>
                            )}
                        </div>
                        {templates.length > 0 ? (
                            <Select value={selectedTemplateName} onValueChange={handleTemplateSelect}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Seleccionar plantilla" />
                                </SelectTrigger>
                                <SelectContent>
                                    {templates.map((t) => (
                                        <SelectItem key={`${t.name}-${t.language}`} value={t.name}>
                                            {t.name} ({t.language})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <Input
                                value={selectedTemplateName}
                                onChange={(e) => onUpdateConfig(node.id, { template_name: e.target.value })}
                                placeholder="nombre_de_plantilla"
                            />
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Idioma de plantilla</Label>
                        <Input
                            value={String(node.data.config.template_language || 'es')}
                            onChange={(e) => onUpdateConfig(node.id, { template_language: e.target.value })}
                            placeholder="es"
                        />
                        <p className="text-xs text-muted-foreground">Código de idioma: es, en, pt_BR, etc.</p>
                    </div>

                    {/* Template Parameters */}
                    {selectedTemplate && selectedTemplate.parameters.length > 0 && (
                        <div className="space-y-3">
                            <div className="rounded-md border border-green-200 bg-green-50 p-2 dark:border-green-800 dark:bg-green-950">
                                <p className="text-xs font-medium text-green-800 dark:text-green-200">
                                    Parámetros de la plantilla ({selectedTemplate.parameters.length})
                                </p>
                            </div>

                            {selectedTemplate.parameters.map((param) => (
                                <div key={param.name} className="space-y-1">
                                    <Label className="text-xs">
                                        {param.component === 'button' ? (
                                            <>🔗 {param.button_text} - URL param</>
                                        ) : (
                                            <>
                                                {param.component === 'header' ? '📝 Header: ' : '📄 Body: '}
                                                <span className="font-mono">{`{{${param.name}}}`}</span>
                                            </>
                                        )}
                                    </Label>
                                    <Input
                                        value={templateParams[param.name] || ''}
                                        onChange={(e) => handleParamChange(param.name, e.target.value)}
                                        placeholder={param.example || `Valor para ${param.name}`}
                                        className="text-sm"
                                    />
                                    {param.example && <p className="text-xs text-muted-foreground">Ej: {param.example}</p>}
                                </div>
                            ))}

                            <div className="rounded-md bg-muted/30 p-2">
                                <p className="text-xs text-muted-foreground">
                                    💡 Usa variables como <code className="rounded bg-muted px-1">{'{{contact.name}}'}</code> para datos dinámicos
                                </p>
                            </div>
                        </div>
                    )}
                </>
            )}

            <div className="rounded-md bg-muted/50 p-2">
                <p className="mb-2 text-xs font-medium">Variables disponibles</p>
                <div className="flex flex-wrap gap-1">
                    {templateVariables.slice(0, 6).map((v) => (
                        <span key={v.token} className="rounded bg-background px-1.5 py-0.5 font-mono text-xs" title={v.description}>
                            {v.token}
                        </span>
                    ))}
                </div>
            </div>
        </>
    );
}

type DynamicSchemaField = {
    type: string;
    description: string;
    children: { key: string; type: string; description: string }[];
};

/**
 * Build dynamic output schema for workflow triggers based on workflow configuration.
 */
function buildWorkflowTriggerSchema(
    workflow: WorkflowOption | undefined,
    baseSchema: Map<string, DynamicSchemaField>,
): Map<string, DynamicSchemaField> {
    const schema = new Map(baseSchema);

    // Add invoice fields if workflow requires/allows invoice
    if (workflow?.invoice_requirement === 'required' || workflow?.invoice_requirement === 'optional') {
        schema.set('invoice', {
            type: 'object',
            description: workflow.invoice_requirement === 'required' ? 'Factura asociada (requerida)' : 'Factura asociada (opcional)',
            children: [
                { key: 'id', type: 'string', description: 'ID de la factura' },
                { key: 'type', type: 'string', description: 'Tipo de documento' },
                { key: 'document_number', type: 'string', description: 'Número de documento' },
                { key: 'number', type: 'string', description: 'Alias de document_number' },
                { key: 'total_amount', type: 'number', description: 'Monto total' },
                { key: 'issue_date', type: 'string', description: 'Fecha de emisión' },
                { key: 'due_date', type: 'string', description: 'Fecha de vencimiento' },
                { key: 'status', type: 'string', description: 'Estado de la factura' },
            ],
        });
    }

    // Add custom fields from workflow metadata
    if (workflow?.fields && workflow.fields.length > 0) {
        const metadataChildren: { key: string; type: string; description: string }[] = workflow.fields.map((field) => ({
            key: field.key,
            type: field.type === 'number' ? 'number' : field.type === 'boolean' ? 'boolean' : 'string',
            description: `${field.name}${field.is_required ? ' (requerido)' : ''}`,
        }));

        schema.set('metadata', {
            type: 'object',
            description: 'Campos personalizados del proceso',
            children: metadataChildren,
        });
    }

    return schema;
}

/**
 * Panel that displays the output schema for a node.
 */
function OutputSchemaPanel({
    nodeType,
    nodeTypeRegistry,
    workflows,
    nodeConfig,
}: {
    nodeType: string;
    nodeTypeRegistry: NodeTypeRegistry;
    workflows: WorkflowOption[];
    nodeConfig: Record<string, unknown>;
}) {
    const [isOpen, setIsOpen] = useState(true);

    const groupedSchema = useMemo(() => {
        const baseSchema = getGroupedOutputSchema(nodeTypeRegistry, nodeType);

        // For workflow triggers, enhance schema based on selected workflow
        if (nodeType === 'workflow.stage_entered' && nodeConfig?.workflow_id) {
            const selectedWorkflow = workflows.find((w) => w.id === nodeConfig.workflow_id);
            return buildWorkflowTriggerSchema(selectedWorkflow, baseSchema);
        }

        return baseSchema;
    }, [nodeTypeRegistry, nodeType, workflows, nodeConfig]);

    if (groupedSchema.size === 0) {
        return null;
    }

    return (
        <div className="mt-4 border-t pt-4">
            <Collapsible open={isOpen} onOpenChange={setIsOpen}>
                <CollapsibleTrigger className="flex w-full items-center justify-between text-sm font-medium">
                    <span>📤 Datos de salida</span>
                    {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </CollapsibleTrigger>
                <CollapsibleContent className="mt-3 space-y-2">
                    <p className="text-xs text-muted-foreground">Estos datos estarán disponibles para los nodos siguientes:</p>
                    <div className="max-h-[300px] space-y-2 overflow-y-auto">
                        {Array.from(groupedSchema.entries()).map(([key, group]) => (
                            <OutputSchemaGroup key={key} name={key} group={group} />
                        ))}
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    );
}

function OutputSchemaGroup({
    name,
    group,
}: {
    name: string;
    group: { type: string; description: string; children: { key: string; type: string; description: string }[] };
}) {
    const [isOpen, setIsOpen] = useState(false);
    const hasChildren = group.children.length > 0;

    const typeColors: Record<string, string> = {
        string: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        number: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        boolean: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
        object: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        array: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300',
        mixed: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    };

    if (!hasChildren) {
        return (
            <div className="rounded border bg-muted/30 p-2">
                <div className="flex items-center justify-between">
                    <code className="text-xs font-medium">{`{{${name}}}`}</code>
                    <span className={`rounded px-1.5 py-0.5 text-[10px] ${typeColors[group.type] ?? typeColors.mixed}`}>{group.type}</span>
                </div>
                {group.description && <p className="mt-1 text-xs text-muted-foreground">{group.description}</p>}
            </div>
        );
    }

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen} className="rounded border bg-muted/30">
            <CollapsibleTrigger className="flex w-full items-center justify-between p-2">
                <div className="flex items-center gap-2">
                    {isOpen ? <ChevronDown className="h-3 w-3" /> : <ChevronRight className="h-3 w-3" />}
                    <code className="text-xs font-medium">{name}</code>
                </div>
                <span className={`rounded px-1.5 py-0.5 text-[10px] ${typeColors.object}`}>object</span>
            </CollapsibleTrigger>
            <CollapsibleContent className="border-t px-2 pb-2">
                {group.description && <p className="py-1 text-xs text-muted-foreground">{group.description}</p>}
                <div className="space-y-1">
                    {group.children.map((child) => (
                        <div key={child.key} className="flex items-start justify-between rounded bg-background/50 px-2 py-1">
                            <div className="min-w-0 flex-1">
                                <code className="text-[11px]">{`{{${name}.${child.key}}}`}</code>
                                {child.description && <p className="truncate text-[10px] text-muted-foreground">{child.description}</p>}
                            </div>
                            <span className={`ml-2 shrink-0 rounded px-1 py-0.5 text-[10px] ${typeColors[child.type] ?? typeColors.mixed}`}>
                                {child.type}
                            </span>
                        </div>
                    ))}
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}
