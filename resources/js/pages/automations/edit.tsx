import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Workflow as WorkflowIcon } from 'lucide-react';

import { AutomationBuilder, type AutomationEdge, type AutomationNode } from '@/components/automations/automation-builder';
import { TestPanel } from '@/components/automations/test-panel';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type WorkflowStageOption = { id: string; name: string };

type WorkflowOption = {
    id: string;
    name: string;
    stages: WorkflowStageOption[];
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

type WhatsappAccountOption = {
    id: string;
    name: string;
    display_phone_number: string | null;
    business_account_id: string | null;
};

type Trigger = {
    workflow_id: string;
    workflow_stage_id: string;
} | null;

interface DefinitionNode {
    id: string;
    type: string;
    position?: { x: number; y: number };
    config: Record<string, unknown>;
}

interface Props {
    automation: { id: string; name: string; is_active: boolean; published_version: number };
    trigger: Trigger;
    workflows: WorkflowOption[];
    definition: {
        nodes?: DefinitionNode[];
        edges?: Array<{ from: string; to: string; sourceHandle?: string | null; targetHandle?: string | null }>;
    };
    templateVariables?: TemplateVariable[];
    telegramBots?: TelegramBotOption[];
    whatsappAccounts?: WhatsappAccountOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Automatizaciones', href: '/automations' },
    { title: 'Editar', href: '#' },
];

function buildInitialNodes(definition: Props['definition'], trigger: Trigger): AutomationNode[] {
    const nodes: AutomationNode[] = [];

    if (definition?.nodes && definition.nodes.length > 0) {
        for (const n of definition.nodes) {
            let nodeType: string;
            let label: string;

            switch (n.type) {
                case 'workflow.stage_entered':
                    nodeType = 'trigger';
                    label = 'Stage Entered';
                    break;
                case 'telegram.send_message':
                    nodeType = 'telegram';
                    label = 'Telegram Message';
                    break;
                case 'whatsapp.send_message':
                    nodeType = 'whatsapp';
                    label = 'WhatsApp Message';
                    break;
                case 'logic.condition':
                    nodeType = 'condition';
                    label = 'Condición';
                    break;
                case 'http.webhook':
                default:
                    nodeType = 'webhook';
                    label = 'HTTP Webhook';
                    break;
            }

            nodes.push({
                id: n.id,
                type: nodeType,
                position: n.position ?? { x: 100, y: 200 },
                data: {
                    label,
                    nodeType: n.type,
                    config: n.config,
                },
            });
        }
        return nodes;
    }

    // Fallback: build from trigger
    nodes.push({
        id: 'trigger-1',
        type: 'trigger',
        position: { x: 100, y: 200 },
        data: {
            label: 'Stage Entered',
            nodeType: 'workflow.stage_entered',
            config: {
                workflow_id: trigger?.workflow_id ?? '',
                stage_id: trigger?.workflow_stage_id ?? '',
            },
        },
    });

    return nodes;
}

function buildInitialEdges(definition: Props['definition']): AutomationEdge[] {
    if (!definition?.edges) return [];

    return definition.edges.map((e, idx) => ({
        id: `edge-${idx}`,
        source: e.from,
        target: e.to,
        sourceHandle: e.sourceHandle ?? undefined,
        targetHandle: e.targetHandle ?? undefined,
        animated: true,
    }));
}

export default function AutomationsEdit({ automation, trigger, workflows, definition, templateVariables, telegramBots, whatsappAccounts }: Props) {
    const initialNodes = buildInitialNodes(definition, trigger);
    const initialEdges = buildInitialEdges(definition);

    const handleSave = (nodes: AutomationNode[], edges: AutomationEdge[], name: string, isActive: boolean) => {
        const triggerNode = nodes.find((n) => n.data.nodeType === 'workflow.stage_entered');

        router.patch(`/automations/${automation.id}`, {
            name,
            is_active: isActive,
            trigger_workflow_id: triggerNode?.data.config.workflow_id ?? '',
            trigger_stage_id: triggerNode?.data.config.stage_id ?? '',
            nodes: nodes.map((n) => ({
                id: n.id,
                type: n.data.nodeType,
                position: n.position,
                config: n.data.config,
            })),
            edges: edges.map((e) => ({
                from: e.source,
                to: e.target,
                sourceHandle: e.sourceHandle,
                targetHandle: e.targetHandle,
            })),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar automatización - ${automation.name}`} />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <WorkflowIcon className="h-6 w-6" />
                            Editar automatización
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Versión publicada: {automation.published_version}</p>
                    </div>

                    <Link href="/automations">
                        <Button variant="outline">
                            <ChevronLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <AutomationBuilder
                    workflows={workflows}
                    templateVariables={templateVariables ?? []}
                    telegramBots={telegramBots ?? []}
                    whatsappAccounts={whatsappAccounts ?? []}
                    initialNodes={initialNodes}
                    initialEdges={initialEdges}
                    onSave={handleSave}
                    automationName={automation.name}
                    automationIsActive={automation.is_active}
                />

                <div className="mt-6">
                    <TestPanel automationId={automation.id} workflows={workflows} selectedWorkflowId={trigger?.workflow_id} />
                </div>
            </div>
        </AppLayout>
    );
}
