import { Head, Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Clock, History } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';

interface Run {
    id: string;
    status: string;
    trigger_event_key: string | null;
    subject_type: string | null;
    subject_id: string | null;
    pending_nodes: number;
    version_number: number | null;
    error: string | null;
    started_at: string | null;
    finished_at: string | null;
    created_at: string | null;
}

interface Automation {
    id: string;
    name: string;
    is_active: boolean;
}

interface Props {
    automation: Automation;
    runs: PaginatedData<Run>;
}

function getStatusBadge(status: string) {
    switch (status) {
        case 'completed':
            return <Badge variant="default" className="bg-green-600">Completado</Badge>;
        case 'running':
            return <Badge variant="default" className="bg-blue-600">En ejecución</Badge>;
        case 'failed':
            return <Badge variant="destructive">Fallido</Badge>;
        case 'pending':
            return <Badge variant="secondary">Pendiente</Badge>;
        default:
            return <Badge variant="outline">{status}</Badge>;
    }
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    try {
        return formatDistanceToNow(new Date(dateStr), { addSuffix: true, locale: es });
    } catch {
        return '-';
    }
}

function formatEventKey(eventKey: string | null): string {
    if (!eventKey) return '-';
    // Convert workflow.stage_entered to something nicer
    const parts = eventKey.split('.');
    if (parts.length >= 2) {
        return parts[1].replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }
    return eventKey;
}

export default function AutomationRunsIndex({ automation, runs }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Automatizaciones', href: '/automations' },
        { title: automation.name, href: `/automations/${automation.id}/edit` },
        { title: 'Historial', href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Historial - ${automation.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <History className="h-6 w-6" />
                            Historial de ejecuciones
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {automation.name} - {runs.total} ejecuciones
                        </p>
                    </div>

                    <Link href={`/automations/${automation.id}/edit`}>
                        <Button variant="outline">
                            <ChevronLeft className="mr-2 h-4 w-4" />
                            Volver al editor
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Ejecuciones</CardTitle>
                        <CardDescription>
                            Listado de las ejecuciones de esta automatización ordenadas por fecha.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {runs.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <Clock className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">Sin ejecuciones</h3>
                                <p className="text-muted-foreground">
                                    Esta automatización aún no se ha ejecutado.
                                </p>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Evento</TableHead>
                                            <TableHead>Versión</TableHead>
                                            <TableHead>Nodos pendientes</TableHead>
                                            <TableHead>Iniciado</TableHead>
                                            <TableHead>Finalizado</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {runs.data.map((run) => (
                                            <TableRow key={run.id}>
                                                <TableCell>{getStatusBadge(run.status)}</TableCell>
                                                <TableCell className="font-medium">
                                                    {formatEventKey(run.trigger_event_key)}
                                                </TableCell>
                                                <TableCell>v{run.version_number ?? '-'}</TableCell>
                                                <TableCell>
                                                    {run.pending_nodes > 0 ? (
                                                        <Badge variant="outline">{run.pending_nodes}</Badge>
                                                    ) : (
                                                        '-'
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {formatDate(run.started_at)}
                                                </TableCell>
                                                <TableCell className="text-muted-foreground">
                                                    {formatDate(run.finished_at)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Link href={`/automations/${automation.id}/runs/${run.id}`}>
                                                        <Button variant="ghost" size="sm">
                                                            Ver detalles
                                                            <ChevronRight className="ml-1 h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {/* Pagination */}
                                {runs.last_page > 1 && (
                                    <div className="mt-4 flex items-center justify-between border-t pt-4">
                                        <p className="text-sm text-muted-foreground">
                                            Página {runs.current_page} de {runs.last_page}
                                        </p>
                                        <div className="flex gap-2">
                                            {runs.prev_page_url && (
                                                <Link href={runs.prev_page_url}>
                                                    <Button variant="outline" size="sm">
                                                        <ChevronLeft className="mr-1 h-4 w-4" />
                                                        Anterior
                                                    </Button>
                                                </Link>
                                            )}
                                            {runs.next_page_url && (
                                                <Link href={runs.next_page_url}>
                                                    <Button variant="outline" size="sm">
                                                        Siguiente
                                                        <ChevronRight className="ml-1 h-4 w-4" />
                                                    </Button>
                                                </Link>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
