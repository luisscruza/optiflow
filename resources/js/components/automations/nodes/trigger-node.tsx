import { Handle, Position, type NodeProps } from '@xyflow/react';
import * as LucideIcons from 'lucide-react';
import { Zap } from 'lucide-react';
import type { AutomationNodeData } from '../automation-builder';
import { useAutomationContext } from '../automation-context';
import { getNodeType } from '../registry';

/**
 * Get icon component from Lucide by name.
 */
function getIconComponent(iconName: string): React.ComponentType<{ className?: string }> {
    const Icon = (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[iconName];
    return Icon ?? Zap;
}

export function TriggerNode({ data, selected }: NodeProps<AutomationNodeData>) {
    const { nodeTypeRegistry } = useAutomationContext();
    const nodeTypeDef = getNodeType(nodeTypeRegistry, data.nodeType);

    const label = nodeTypeDef?.label ?? data.label;
    const Icon = getIconComponent(nodeTypeDef?.icon ?? 'Zap');

    return (
        <div
            className={`min-w-[160px] rounded-lg border-2 bg-gradient-to-br from-amber-50 to-amber-100 p-3 shadow-md transition-all dark:from-amber-950 dark:to-amber-900 ${
                selected ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-amber-300 dark:border-amber-700'
            }`}
        >
            <div className="flex items-center gap-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-md bg-amber-500 text-white">
                    <Icon className="h-4 w-4" />
                </div>
                <div>
                    <p className="text-xs font-medium tracking-wide text-amber-600 uppercase dark:text-amber-400">Trigger</p>
                    <p className="text-sm font-semibold text-amber-900 dark:text-amber-100">{label}</p>
                </div>
            </div>

            {data.config?.stage_id && (
                <div className="mt-2 rounded bg-amber-200/50 px-2 py-1 text-xs text-amber-700 dark:bg-amber-800/50 dark:text-amber-300">
                    Etapa configurada
                </div>
            )}

            <Handle type="source" position={Position.Right} className="!h-3 !w-3 !border-2 !border-amber-500 !bg-white dark:!bg-amber-900" />
        </div>
    );
}
