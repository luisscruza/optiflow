import { Head, Link } from '@inertiajs/react';
import { Plus, Workflow as WorkflowIcon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface AutomationTrigger {
    id: string;
    event_key: string;
    workflow_id?: string | null;
    workflow_stage_id?: string | null;
    is_active: boolean;
}

interface Automation {
    id: string;
    name: string;
    is_active: boolean;
    published_version: number;
    created_at?: string | null;
    triggers: AutomationTrigger[];
}

interface Props {
    automations: Automation[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Automatizaciones',
        href: '/automations',
    },
];

export default function AutomationsIndex({ automations }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Automatizaciones" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <WorkflowIcon className="h-6 w-6" />
                            Automatizaciones
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Crea flujos de acciones que se ejecutan cuando ocurre un evento.</p>
                    </div>

                    <Link href="/automations/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva automatización
                        </Button>
                    </Link>
                </div>

                {automations.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <WorkflowIcon className="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">No hay automatizaciones</h3>
                            <p className="mb-4 text-muted-foreground">Crea tu primera automatización para ejecutar acciones automáticamente.</p>
                            <Link href="/automations/create">
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear automatización
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {automations.map((automation) => (
                            <Card key={automation.id} className="transition-shadow hover:shadow-lg">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <CardTitle className="text-lg">{automation.name}</CardTitle>
                                            <CardDescription>Versión {automation.published_version}</CardDescription>
                                        </div>

                                        <Badge variant={automation.is_active ? 'default' : 'secondary'}>
                                            {automation.is_active ? 'Activa' : 'Inactiva'}
                                        </Badge>
                                    </div>
                                </CardHeader>

                                <CardContent className="space-y-3">
                                    <div className="text-sm text-muted-foreground">Disparadores: {automation.triggers.length}</div>

                                    <Link href={`/automations/${automation.id}/edit`} className="block">
                                        <Button variant="outline" className="w-full">
                                            Editar
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
