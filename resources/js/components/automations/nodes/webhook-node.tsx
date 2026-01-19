import { Handle, Position, type NodeProps } from '@xyflow/react';
import { Webhook } from 'lucide-react';
import type { AutomationNodeData } from '../automation-builder';

export function WebhookNode({ data, selected }: NodeProps<AutomationNodeData>) {
    const hasUrl = Boolean(data.config?.url);

    return (
        <div
            className={`min-w-[160px] rounded-lg border-2 bg-gradient-to-br from-blue-50 to-blue-100 p-3 shadow-md transition-all dark:from-blue-950 dark:to-blue-900 ${
                selected ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-blue-300 dark:border-blue-700'
            }`}
        >
            <Handle type="target" position={Position.Left} className="!h-3 !w-3 !border-2 !border-blue-500 !bg-white dark:!bg-blue-900" />

            <div className="flex items-center gap-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-md bg-blue-500 text-white">
                    <Webhook className="h-4 w-4" />
                </div>
                <div>
                    <p className="text-xs font-medium tracking-wide text-blue-600 uppercase dark:text-blue-400">Acción</p>
                    <p className="text-sm font-semibold text-blue-900 dark:text-blue-100">{data.label}</p>
                </div>
            </div>

            {hasUrl && (
                <div className="mt-2 truncate rounded bg-blue-200/50 px-2 py-1 text-xs text-blue-700 dark:bg-blue-800/50 dark:text-blue-300">
                    {String(data.config.url).slice(0, 30)}...
                </div>
            )}

            <Handle type="source" position={Position.Right} className="!h-3 !w-3 !border-2 !border-blue-500 !bg-white dark:!bg-blue-900" />
        </div>
    );
}
