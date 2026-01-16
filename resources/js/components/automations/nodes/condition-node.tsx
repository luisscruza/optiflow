import { Handle, Position, type NodeProps } from '@xyflow/react';
import { GitBranch } from 'lucide-react';

interface ConditionNodeData {
    label: string;
    nodeType: string;
    config: {
        field?: string;
        operator?: string;
        value?: string;
    };
}

const operatorLabels: Record<string, string> = {
    equals: '=',
    not_equals: '≠',
    contains: 'contiene',
    greater_than: '>',
    less_than: '<',
    is_empty: 'vacío',
    is_not_empty: 'no vacío',
};

export function ConditionNode({ data, selected }: NodeProps) {
    const nodeData = data as ConditionNodeData;
    const hasConfig = nodeData.config.field && nodeData.config.operator;

    return (
        <div
            className={`min-w-[180px] rounded-lg border-2 bg-gradient-to-br from-purple-50 to-purple-100 p-3 shadow-md transition-all dark:from-purple-950 dark:to-purple-900 ${
                selected ? 'border-purple-500 ring-2 ring-purple-300' : 'border-purple-300 dark:border-purple-700'
            }`}
        >
            <Handle type="target" position={Position.Left} className="!h-3 !w-3 !border-2 !border-purple-500 !bg-white dark:!bg-purple-900" />

            <div className="flex items-center gap-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-500 text-white">
                    <GitBranch className="h-4 w-4" />
                </div>
                <div>
                    <div className="text-sm font-semibold text-purple-900 dark:text-purple-100">Condición</div>
                    <div className="text-xs text-purple-600 dark:text-purple-400">Si / Entonces</div>
                </div>
            </div>

            {hasConfig && (
                <div className="mt-2 rounded bg-purple-200/50 px-2 py-1 text-xs text-purple-700 dark:bg-purple-800/50 dark:text-purple-300">
                    {nodeData.config.field?.replace(/[{}]/g, '')} {operatorLabels[nodeData.config.operator || ''] || nodeData.config.operator}
                    {nodeData.config.value ? ` "${nodeData.config.value}"` : ''}
                </div>
            )}

            {/* True branch - top right */}
            <Handle
                type="source"
                position={Position.Right}
                id="true"
                style={{ top: '30%' }}
                className="!h-3 !w-3 !border-2 !border-green-500 !bg-green-100 dark:!bg-green-900"
            />
            <div className="absolute top-[25%] right-[-30px] text-xs font-medium text-green-600">Sí</div>

            {/* False branch - bottom right */}
            <Handle
                type="source"
                position={Position.Right}
                id="false"
                style={{ top: '70%' }}
                className="!h-3 !w-3 !border-2 !border-red-500 !bg-red-100 dark:!bg-red-900"
            />
            <div className="absolute top-[65%] right-[-28px] text-xs font-medium text-red-600">No</div>
        </div>
    );
}
