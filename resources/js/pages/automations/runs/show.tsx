import { Head, Link } from '@inertiajs/react';
import {
    Background,
    Controls,
    MiniMap,
    ReactFlow,
    type Edge,
    type NodeTypes,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { ChevronLeft, Clock, History, AlertCircle, CheckCircle2, Loader2, XCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import { formatDistanceToNow, format } from 'date-fns';
import { es } from 'date-fns/locale';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { RunViewerNode, type NodeRunData, type RunViewerNode as RunViewerNodeType } from '@/components/automations/nodes/run-viewer-node';

interface EnrichedNode {
    id: string;
    type: string;
    position: { x: number; y: number };
    config: Record<string, unknown>;
    run: NodeRunData | null;
}

interface DefinitionEdge {
    from: string;
    to: string;
    sourceHandle?: string | null;
    targetHandle?: string | null;
}

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
}

interface Automation {
    id: string;
    name: string;
}

interface Props {
    automation: Automation;
    run: Run;
    definition: {
        nodes: EnrichedNode[];
        edges: DefinitionEdge[];
    };
}

const nodeTypes: NodeTypes = {
    runViewer: RunViewerNode,
};

function getNodeLabel(nodeType: string): string {
    switch (nodeType) {
        case 'workflow.stage_entered':
            return 'Proceso cambio de etapa';
        case 'http.webhook':
            return 'HTTP Webhook';
        case 'telegram.send_message':
            return 'Telegram Message';
        case 'whatsapp.send_message':
            return 'WhatsApp Message';
        case 'logic.condition':
            return 'Condición';
        default:
            return 'Nodo';
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

function formatFullDate(dateStr: string | null): string {
    if (!dateStr) return '-';
    try {
        return format(new Date(dateStr), 'dd/MM/yyyy HH:mm:ss', { locale: es });
    } catch {
        return '-';
    }
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'completed':
            return <CheckCircle2 className="h-5 w-5 text-green-600" />;
        case 'running':
            return <Loader2 className="h-5 w-5 animate-spin text-blue-600" />;
        case 'failed':
            return <XCircle className="h-5 w-5 text-red-600" />;
        case 'pending':
            return <Clock className="h-5 w-5 text-yellow-600" />;
        default:
            return <AlertCircle className="h-5 w-5 text-gray-600" />;
    }
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

export default function AutomationRunShow({ automation, run, definition }: Props) {
    const [selectedNodeId, setSelectedNodeId] = useState<string | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Automatizaciones', href: '/automations' },
        { title: automation.name, href: `/automations/${automation.id}/edit` },
        { title: 'Historial', href: `/automations/${automation.id}/runs` },
        { title: `Ejecución`, href: '#' },
    ];

    // Convert definition nodes to React Flow nodes
    const nodes = useMemo((): RunViewerNodeType[] => {
        return definition.nodes.map((node) => ({
            id: node.id,
            type: 'runViewer',
            position: node.position ?? { x: 100, y: 200 },
            data: {
                label: getNodeLabel(node.type),
                nodeType: node.type,
                config: node.config,
                run: node.run,
            },
        }));
    }, [definition.nodes]);

    // Convert definition edges to React Flow edges
    const edges = useMemo((): Edge[] => {
        return definition.edges.map((edge, idx) => {
            // Determine if this edge was executed
            const sourceNode = definition.nodes.find((n) => n.id === edge.from);
            const targetNode = definition.nodes.find((n) => n.id === edge.to);
            const wasExecuted = sourceNode?.run?.status === 'completed' && targetNode?.run !== null;

            // For condition nodes, check if the edge matches the branch taken
            let matchesBranch = true;
            if (sourceNode?.type === 'logic.condition' && sourceNode?.run?.output?.branch) {
                const branch = sourceNode.run.output.branch as string;
                matchesBranch = edge.sourceHandle === branch;
            }

            const isActive = wasExecuted && matchesBranch;

            return {
                id: `edge-${idx}`,
                source: edge.from,
                target: edge.to,
                sourceHandle: edge.sourceHandle ?? undefined,
                targetHandle: edge.targetHandle ?? undefined,
                animated: isActive,
                style: {
                    stroke: isActive ? '#22c55e' : '#9ca3af',
                    strokeWidth: isActive ? 2 : 1,
                },
            };
        });
    }, [definition.edges, definition.nodes]);

    // Get the selected node's run data for the inspector
    const selectedNodeRun = useMemo(() => {
        if (!selectedNodeId) return null;
        const node = definition.nodes.find((n) => n.id === selectedNodeId);
        return node?.run ?? null;
    }, [selectedNodeId, definition.nodes]);

    const selectedNode = useMemo(() => {
        if (!selectedNodeId) return null;
        return definition.nodes.find((n) => n.id === selectedNodeId) ?? null;
    }, [selectedNodeId, definition.nodes]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ejecución - ${automation.name}`} />

            <div className="flex h-[calc(100vh-64px)] flex-col">
                {/* Header */}
                <div className="border-b bg-background px-4 py-4 sm:px-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                {getStatusIcon(run.status)}
                                <div>
                                    <h1 className="text-lg font-bold text-gray-900 dark:text-gray-100">
                                        Ejecución de {automation.name}
                                    </h1>
                                    <p className="text-sm text-muted-foreground">
                                        Versión {run.version_number ?? '-'} • {formatDate(run.started_at)}
                                    </p>
                                </div>
                            </div>
                            {getStatusBadge(run.status)}
                        </div>

                        <Link href={`/automations/${automation.id}/runs`}>
                            <Button variant="outline" size="sm">
                                <ChevronLeft className="mr-2 h-4 w-4" />
                                Volver al historial
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex flex-1 overflow-hidden">
                    {/* React Flow Canvas */}
                    <div className="flex-1">
                        <ReactFlow
                            nodes={nodes}
                            edges={edges}
                            nodeTypes={nodeTypes}
                            onNodeClick={(_, node) => setSelectedNodeId(node.id)}
                            fitView
                            fitViewOptions={{ padding: 0.2 }}
                            minZoom={0.3}
                            maxZoom={1.5}
                            proOptions={{ hideAttribution: true }}
                        >
                            <Background />
                            <Controls />
                            <MiniMap
                                nodeColor={(node) => {
                                    const run = (node.data as { run?: NodeRunData | null }).run;
                                    if (!run) return '#9ca3af';
                                    switch (run.status) {
                                        case 'completed':
                                            return '#22c55e';
                                        case 'running':
                                            return '#3b82f6';
                                        case 'failed':
                                            return '#ef4444';
                                        default:
                                            return '#9ca3af';
                                    }
                                }}
                            />
                        </ReactFlow>
                    </div>

                    {/* Inspector Panel */}
                    <div className="w-96 border-l bg-background overflow-y-auto">
                        <Tabs defaultValue="run" className="h-full">
                            <div className="border-b px-4 py-2">
                                <TabsList className="w-full">
                                    <TabsTrigger value="run" className="flex-1">
                                        <History className="mr-2 h-4 w-4" />
                                        Ejecución
                                    </TabsTrigger>
                                    <TabsTrigger value="node" className="flex-1">
                                        Nodo
                                    </TabsTrigger>
                                </TabsList>
                            </div>

                            <TabsContent value="run" className="m-0 p-4">
                                <Card>
                                    <CardHeader className="pb-3">
                                        <CardTitle className="text-base">Detalles de la ejecución</CardTitle>
                                        <CardDescription>
                                            Información general sobre esta ejecución.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Estado</p>
                                            <div className="mt-1">{getStatusBadge(run.status)}</div>
                                        </div>

                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Evento</p>
                                            <p className="mt-1 text-sm">{run.trigger_event_key ?? '-'}</p>
                                        </div>

                                        {run.subject_type && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Sujeto</p>
                                                <p className="mt-1 text-sm font-mono">
                                                    {run.subject_type} #{run.subject_id}
                                                </p>
                                            </div>
                                        )}

                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Nodos pendientes</p>
                                            <p className="mt-1 text-sm">{run.pending_nodes}</p>
                                        </div>

                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Iniciado</p>
                                            <p className="mt-1 text-sm">{formatFullDate(run.started_at)}</p>
                                        </div>

                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Finalizado</p>
                                            <p className="mt-1 text-sm">{formatFullDate(run.finished_at)}</p>
                                        </div>

                                        {run.error && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Error</p>
                                                <p className="mt-1 rounded bg-red-50 p-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
                                                    {run.error}
                                                </p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </TabsContent>

                            <TabsContent value="node" className="m-0 p-4">
                                {selectedNode ? (
                                    <Card>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="text-base">
                                                {getNodeLabel(selectedNode.type)}
                                            </CardTitle>
                                            <CardDescription>
                                                {selectedNode.id}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {selectedNodeRun ? (
                                                <>
                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Estado</p>
                                                        <div className="mt-1">
                                                            <Badge variant={
                                                                selectedNodeRun.status === 'completed' ? 'default' :
                                                                selectedNodeRun.status === 'failed' ? 'destructive' :
                                                                'secondary'
                                                            }>
                                                                {selectedNodeRun.status}
                                                            </Badge>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Intentos</p>
                                                        <p className="mt-1 text-sm">{selectedNodeRun.attempts}</p>
                                                    </div>

                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Iniciado</p>
                                                        <p className="mt-1 text-sm">{formatFullDate(selectedNodeRun.started_at)}</p>
                                                    </div>

                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Finalizado</p>
                                                        <p className="mt-1 text-sm">{formatFullDate(selectedNodeRun.finished_at)}</p>
                                                    </div>

                                                    {selectedNodeRun.input && Object.keys(selectedNodeRun.input).length > 0 && (
                                                        <div>
                                                            <p className="text-sm font-medium text-muted-foreground">Input</p>
                                                            <pre className="mt-1 max-h-40 overflow-auto rounded bg-muted p-2 text-xs">
                                                                {JSON.stringify(selectedNodeRun.input, null, 2)}
                                                            </pre>
                                                        </div>
                                                    )}

                                                    {selectedNodeRun.output && Object.keys(selectedNodeRun.output).length > 0 && (
                                                        <div>
                                                            <p className="text-sm font-medium text-muted-foreground">Output</p>
                                                            <pre className="mt-1 max-h-40 overflow-auto rounded bg-muted p-2 text-xs">
                                                                {JSON.stringify(selectedNodeRun.output, null, 2)}
                                                            </pre>
                                                        </div>
                                                    )}

                                                    {selectedNodeRun.error && (
                                                        <div>
                                                            <p className="text-sm font-medium text-muted-foreground">Error</p>
                                                            <p className="mt-1 rounded bg-red-50 p-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">
                                                                {selectedNodeRun.error}
                                                            </p>
                                                        </div>
                                                    )}
                                                </>
                                            ) : (
                                                <div className="py-8 text-center text-muted-foreground">
                                                    <Clock className="mx-auto mb-2 h-8 w-8" />
                                                    <p className="text-sm">Este nodo no fue ejecutado</p>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="flex h-full items-center justify-center py-12 text-center text-muted-foreground">
                                        <div>
                                            <History className="mx-auto mb-2 h-8 w-8" />
                                            <p className="text-sm">Selecciona un nodo para ver sus detalles</p>
                                        </div>
                                    </div>
                                )}
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
