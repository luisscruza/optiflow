import { Head, Link, router } from '@inertiajs/react';
import { Edit, Eye, LayoutGrid, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Workflow } from '@/types';

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
    const handleDelete = (workflowId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar este flujo de trabajo? Esto eliminará todas las etapas y tareas asociadas.')) {
            router.delete(`/workflows/${workflowId}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Procesos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <LayoutGrid className="h-5 w-5" />
                                Flujos de Trabajo
                            </CardTitle>
                            <CardDescription>Gestiona los procesos de trabajo para el seguimiento de lentes</CardDescription>
                        </div>
                        <Link href="/workflows/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Flujo
                            </Button>
                        </Link>
                    </CardHeader>

                    <CardContent>
                        {workflows.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <LayoutGrid className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">No hay flujos de trabajo</h3>
                                <p className="mb-4 text-muted-foreground">Crea tu primer flujo de trabajo para comenzar a gestionar tus procesos.</p>
                                <Link href="/workflows/create">
                                    <Button>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear Flujo de Trabajo
                                    </Button>
                                </Link>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Etapas</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {workflows.map((workflow) => (
                                        <TableRow key={workflow.id}>
                                            <TableCell className="font-medium">{workflow.name}</TableCell>
                                            <TableCell>{workflow.stages_count ?? 0} etapas</TableCell>
                                            <TableCell>
                                                <Badge variant={workflow.is_active ? 'default' : 'secondary'}>
                                                    {workflow.is_active ? 'Activo' : 'Inactivo'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            •••
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/workflows/${workflow.id}`} className="flex items-center">
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                Ver Tablero
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/workflows/${workflow.id}/edit`} className="flex items-center">
                                                                <Edit className="mr-2 h-4 w-4" />
                                                                Editar
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => handleDelete(workflow.id)}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" />
                                                            Eliminar
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
