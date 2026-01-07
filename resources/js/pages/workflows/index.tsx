import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Clock, Edit, Eye, LayoutGrid, MoreHorizontal, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { Workspace, type BreadcrumbItem, type Workflow } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Procesos',
        href: '/workflows',
    },
];

interface Props {
    workflows: Workflow[];
}

export default function WorkflowsIndex({ workflows }: Props) {
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null } };

    const handleDelete = (workflowId: string) => {
        if (confirm('¿Estás seguro de que deseas eliminar este flujo de trabajo? Esto eliminará todas las etapas y tareas asociadas.')) {
            router.delete(`/workflows/${workflowId}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Procesos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <LayoutGrid className="h-6 w-6" />
                            Flujos de trabajo de {workspace?.current?.name || 'el espacio de trabajo'}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Gestiona los procesos de trabajo para el seguimiento de lentes</p>
                    </div>
                    <Link href="/workflows/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nuevo flujo
                        </Button>
                    </Link>
                </div>

                {workflows.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                            <LayoutGrid className="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">No hay flujos de trabajo</h3>
                            <p className="mb-4 text-muted-foreground">Crea tu primer flujo de trabajo para comenzar a gestionar tus procesos.</p>
                            <Link href="/workflows/create">
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear flujo de trabajo
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {workflows.map((workflow) => {
                            const hasOverdue = (workflow.overdue_jobs_count ?? 0) > 0;
                            const pendingJobs = workflow.pending_jobs_count ?? 0;
                            const overdueJobs = workflow.overdue_jobs_count ?? 0;

                            return (
                                <Card
                                    key={workflow.id}
                                    className={`relative overflow-hidden transition-shadow hover:shadow-lg ${hasOverdue ? 'ring-2 ring-red-200 dark:ring-red-900' : ''}`}
                                >
                                    {/* Status indicator stripe */}
                                    <div
                                        className={`absolute top-0 left-0 h-1 w-full ${
                                            !workflow.is_active
                                                ? 'bg-gray-300'
                                                : hasOverdue
                                                  ? 'bg-red-500'
                                                  : pendingJobs > 0
                                                    ? 'bg-blue-500'
                                                    : 'bg-green-500'
                                        }`}
                                    />

                                    <CardHeader className="pb-3">
                                        <div className="flex items-start justify-between">
                                            <div className="space-y-1">
                                                <CardTitle className="text-lg">{workflow.name}</CardTitle>
                                                <CardDescription>{workflow.stages_count ?? 0} etapas configuradas</CardDescription>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Badge variant={workflow.is_active ? 'default' : 'secondary'}>
                                                    {workflow.is_active ? 'Activo' : 'Inactivo'}
                                                </Badge>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/workflows/${workflow.id}`} className="flex items-center">
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                Ver tablero
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/workflows/${workflow.id}/edit`} className="flex items-center">
                                                                <Edit className="mr-2 h-4 w-4" />
                                                                Editar
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => handleDelete(workflow.id)}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Eliminar
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </div>
                                    </CardHeader>

                                    <CardContent className="pb-3">
                                        {/* Stats Grid */}
                                        <div className="mb-4 grid grid-cols-2 gap-3">
                                            <div className="rounded-lg bg-blue-50 p-3 text-center dark:bg-blue-950">
                                                <div className="flex items-center justify-center gap-1 text-blue-600 dark:text-blue-400">
                                                    <Clock className="h-4 w-4" />
                                                </div>
                                                <p className="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">{pendingJobs}</p>
                                                <p className="text-xs text-blue-600 dark:text-blue-400">Pendientes</p>
                                            </div>

                                            <div
                                                className={`rounded-lg p-3 text-center ${
                                                    hasOverdue ? 'bg-red-50 dark:bg-red-950' : 'bg-gray-50 dark:bg-gray-800'
                                                }`}
                                            >
                                                <div
                                                    className={`flex items-center justify-center gap-1 ${
                                                        hasOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'
                                                    }`}
                                                >
                                                    <AlertTriangle className="h-4 w-4" />
                                                </div>
                                                <p
                                                    className={`mt-1 text-2xl font-bold ${
                                                        hasOverdue ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300'
                                                    }`}
                                                >
                                                    {overdueJobs}
                                                </p>
                                                <p
                                                    className={`text-xs ${
                                                        hasOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'
                                                    }`}
                                                >
                                                    Vencidas
                                                </p>
                                            </div>
                                        </div>

                                        {pendingJobs === 0 && overdueJobs === 0 && (
                                            <p className="py-2 text-center text-sm text-muted-foreground">Sin tareas activas</p>
                                        )}
                                    </CardContent>

                                    <CardFooter className="border-t bg-gray-50/50 pt-3 dark:bg-gray-900/50">
                                        <Link href={`/workflows/${workflow.id}`} className="w-full">
                                            <Button variant="outline" className="w-full">
                                                <Eye className="mr-2 h-4 w-4" />
                                                Ver tablero
                                            </Button>
                                        </Link>
                                    </CardFooter>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
