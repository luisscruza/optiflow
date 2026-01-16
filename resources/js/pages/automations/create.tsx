import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft, Workflow as WorkflowIcon } from 'lucide-react';

import { AutomationBuilder, type AutomationEdge, type AutomationNode } from '@/components/automations/automation-builder';
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

interface Props {
    workflows: WorkflowOption[];
    templateVariables?: TemplateVariable[];
    telegramBots?: TelegramBotOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Automatizaciones', href: '/automations' },
    { title: 'Nueva', href: '/automations/create' },
];

export default function AutomationsCreate({ workflows, templateVariables, telegramBots }: Props) {
    const handleSave = (nodes: AutomationNode[], edges: AutomationEdge[], name: string, isActive: boolean) => {
        const triggerNode = nodes.find((n) => n.data.nodeType === 'workflow.stage_entered');

        router.post('/automations', {
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
            <Head title="Nueva automatización" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <WorkflowIcon className="h-6 w-6" />
                            Nueva automatización
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Arrastra nodos y conéctalos para crear tu flujo de automatización.</p>
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
                    onSave={handleSave}
                />
            </div>
        </AppLayout>
    );
}
