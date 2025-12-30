import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, LayoutGrid } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { KanbanBoard } from '@/components/workflows/kanban-board';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type Contact,
    type CursorPaginatedData,
    type Invoice,
    type Prescription,
    type Workflow,
    type WorkflowJob,
} from '@/types';

interface Props {
    workflow: Workflow;
    invoices?: Invoice[];
    contacts?: Contact[];
    prescriptions?: Prescription[];
    // Stage jobs are passed as flat props like stage_{uuid}_jobs
    [key: `stage_${string}_jobs`]: CursorPaginatedData<WorkflowJob>;
}

export default function WorkflowShow({ workflow, invoices = [], contacts = [], prescriptions = [], ...rest }: Props) {
    // Extract stage jobs from the flat props
    const stageJobs: Record<string, CursorPaginatedData<WorkflowJob>> = {};
    for (const [key, value] of Object.entries(rest)) {
        if (key.startsWith('stage_') && key.endsWith('_jobs')) {
            // Convert prop name back to stage ID: stage_{uuid}_jobs -> uuid (with dashes restored)
            const stageId = key.slice(6, -5).replace(/_/g, '-');
            stageJobs[stageId] = value as CursorPaginatedData<WorkflowJob>;
        }
    }

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Procesos',
            href: '/workflows',
        },
        {
            title: workflow.name,
            href: `/workflows/${workflow.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={workflow.name} />

            <div className="flex h-[calc(100vh-8rem)] flex-col px-4 py-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/workflows">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                        <div className="flex items-center gap-2">
                            <LayoutGrid className="h-5 w-5" />
                            <h1 className="text-2xl font-bold">{workflow.name}</h1>
                            <Badge variant={workflow.is_active ? 'default' : 'secondary'}>{workflow.is_active ? 'Activo' : 'Inactivo'}</Badge>
                        </div>
                    </div>
                    <Link href={`/workflows/${workflow.id}/edit`}>
                        <Button variant="outline" size="sm">
                            <Edit className="mr-2 h-4 w-4" />
                            Editar
                        </Button>
                    </Link>
                </div>

                {/* Kanban Board */}
                <div className="flex-1 overflow-hidden">
                    <KanbanBoard workflow={workflow} stageJobs={stageJobs} invoices={invoices} contacts={contacts} prescriptions={prescriptions} />
                </div>
            </div>
        </AppLayout>
    );
}
