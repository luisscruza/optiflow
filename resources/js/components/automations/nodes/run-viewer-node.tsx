import { Handle, Position, type NodeProps, type Node } from '@xyflow/react';
import { Check, Clock, Loader2, X, Zap, Webhook, MessageCircle, Send, GitBranch } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export interface NodeRunData {
    id: string;
    node_id: string;
    node_type: string;
    status: string;
    attempts: number;
    input: Record<string, unknown> | null;
    output: Record<string, unknown> | null;
    error: string | null;
    started_at: string | null;
    finished_at: string | null;
}

export interface RunViewerNodeData {
    label: string;
    nodeType: string;
    config: Record<string, unknown>;
    run: NodeRunData | null;
}

export type RunViewerNode = Node<RunViewerNodeData>;

function getStatusIcon(status: string | undefined) {
    switch (status) {
        case 'completed':
            return <Check className="h-3 w-3 text-green-600" />;
        case 'running':
            return <Loader2 className="h-3 w-3 animate-spin text-blue-600" />;
        case 'failed':
            return <X className="h-3 w-3 text-red-600" />;
        case 'pending':
            return <Clock className="h-3 w-3 text-yellow-600" />;
        default:
            return null;
    }
}

function getStatusBadgeVariant(status: string | undefined): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'completed':
            return 'default';
        case 'running':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
}

function getStatusLabel(status: string | undefined): string {
    switch (status) {
        case 'completed':
            return 'Completado';
        case 'running':
            return 'Ejecutando';
        case 'failed':
            return 'Fallido';
        case 'pending':
            return 'Pendiente';
        case 'skipped':
            return 'Omitido';
        default:
            return 'Sin ejecutar';
    }
}

function getNodeIcon(nodeType: string) {
    switch (nodeType) {
        case 'workflow.stage_entered':
            return <Zap className="h-4 w-4" />;
        case 'http.webhook':
            return <Webhook className="h-4 w-4" />;
        case 'telegram.send_message':
            return <Send className="h-4 w-4" />;
        case 'whatsapp.send_message':
            return <MessageCircle className="h-4 w-4" />;
        case 'logic.condition':
            return <GitBranch className="h-4 w-4" />;
        default:
            return <Zap className="h-4 w-4" />;
    }
}

function getNodeColors(nodeType: string) {
    switch (nodeType) {
        case 'workflow.stage_entered':
            return {
                gradient: 'from-amber-50 to-amber-100 dark:from-amber-950 dark:to-amber-900',
                border: 'border-amber-300 dark:border-amber-700',
                icon: 'bg-amber-500',
                text: 'text-amber-600 dark:text-amber-400',
                title: 'text-amber-900 dark:text-amber-100',
            };
        case 'http.webhook':
            return {
                gradient: 'from-blue-50 to-blue-100 dark:from-blue-950 dark:to-blue-900',
                border: 'border-blue-300 dark:border-blue-700',
                icon: 'bg-blue-500',
                text: 'text-blue-600 dark:text-blue-400',
                title: 'text-blue-900 dark:text-blue-100',
            };
        case 'telegram.send_message':
            return {
                gradient: 'from-sky-50 to-sky-100 dark:from-sky-950 dark:to-sky-900',
                border: 'border-sky-300 dark:border-sky-700',
                icon: 'bg-sky-500',
                text: 'text-sky-600 dark:text-sky-400',
                title: 'text-sky-900 dark:text-sky-100',
            };
        case 'whatsapp.send_message':
            return {
                gradient: 'from-green-50 to-green-100 dark:from-green-950 dark:to-green-900',
                border: 'border-green-300 dark:border-green-700',
                icon: 'bg-green-600',
                text: 'text-green-600 dark:text-green-400',
                title: 'text-green-900 dark:text-green-100',
            };
        case 'logic.condition':
            return {
                gradient: 'from-purple-50 to-purple-100 dark:from-purple-950 dark:to-purple-900',
                border: 'border-purple-300 dark:border-purple-700',
                icon: 'bg-purple-500',
                text: 'text-purple-600 dark:text-purple-400',
                title: 'text-purple-900 dark:text-purple-100',
            };
        default:
            return {
                gradient: 'from-gray-50 to-gray-100 dark:from-gray-950 dark:to-gray-900',
                border: 'border-gray-300 dark:border-gray-700',
                icon: 'bg-gray-500',
                text: 'text-gray-600 dark:text-gray-400',
                title: 'text-gray-900 dark:text-gray-100',
            };
    }
}

function getNodeLabel(nodeType: string): string {
    switch (nodeType) {
        case 'workflow.stage_entered':
            return 'Trigger';
        case 'http.webhook':
            return 'Webhook';
        case 'telegram.send_message':
            return 'Telegram';
        case 'whatsapp.send_message':
            return 'WhatsApp';
        case 'logic.condition':
            return 'Condición';
        default:
            return 'Nodo';
    }
}

export function RunViewerNode({ data, selected }: NodeProps<RunViewerNodeData>) {
    const colors = getNodeColors(data.nodeType);
    const hasRun = data.run !== null;
    const status = data.run?.status;
    const isCondition = data.nodeType === 'logic.condition';

    return (
        <div
            className={`relative min-w-[180px] rounded-lg border-2 bg-gradient-to-br p-3 shadow-md transition-all ${colors.gradient} ${
                selected ? 'ring-2 ring-blue-500/30' : ''
            } ${colors.border}`}
        >
            {/* Left Handle (input) */}
            <Handle
                type="target"
                position={Position.Left}
                className={`!h-3 !w-3 !border-2 !border-gray-400 !bg-white dark:!bg-gray-900`}
            />

            {/* Main Content */}
            <div className="flex items-center gap-2">
                <div className={`flex h-8 w-8 items-center justify-center rounded-md text-white ${colors.icon}`}>
                    {getNodeIcon(data.nodeType)}
                </div>
                <div className="flex-1">
                    <p className={`text-xs font-medium tracking-wide uppercase ${colors.text}`}>
                        {getNodeLabel(data.nodeType)}
                    </p>
                    <p className={`text-sm font-semibold ${colors.title}`}>{data.label}</p>
                </div>
            </div>

            {/* Status Badge */}
            <div className="mt-2 flex items-center justify-between">
                <Badge variant={getStatusBadgeVariant(status)} className="flex items-center gap-1">
                    {getStatusIcon(status)}
                    {getStatusLabel(status)}
                </Badge>
                {hasRun && data.run!.attempts > 1 && (
                    <span className="text-xs text-muted-foreground">
                        {data.run!.attempts} intentos
                    </span>
                )}
            </div>

            {/* Error Message */}
            {data.run?.error && (
                <div className="mt-2 rounded bg-red-100 px-2 py-1 text-xs text-red-700 dark:bg-red-900/50 dark:text-red-300">
                    {data.run.error.substring(0, 50)}
                    {data.run.error.length > 50 ? '...' : ''}
                </div>
            )}

            {/* Condition branch indicator */}
            {isCondition && data.run?.output?.branch && (
                <div className="mt-2 rounded bg-purple-200/50 px-2 py-1 text-xs text-purple-700 dark:bg-purple-800/50 dark:text-purple-300">
                    Rama: {data.run.output.branch === 'true' ? 'Verdadero' : 'Falso'}
                </div>
            )}

            {/* Right Handle(s) */}
            {isCondition ? (
                <>
                    <Handle
                        type="source"
                        position={Position.Right}
                        id="true"
                        style={{ top: '35%' }}
                        className="!h-3 !w-3 !border-2 !border-green-500 !bg-white dark:!bg-green-900"
                    />
                    <Handle
                        type="source"
                        position={Position.Right}
                        id="false"
                        style={{ top: '65%' }}
                        className="!h-3 !w-3 !border-2 !border-red-500 !bg-white dark:!bg-red-900"
                    />
                </>
            ) : (
                <Handle
                    type="source"
                    position={Position.Right}
                    className={`!h-3 !w-3 !border-2 !border-gray-400 !bg-white dark:!bg-gray-900`}
                />
            )}
        </div>
    );
}
