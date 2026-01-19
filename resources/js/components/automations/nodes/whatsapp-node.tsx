import { Handle, Position, type NodeProps } from '@xyflow/react';
import { MessageCircle } from 'lucide-react';

interface WhatsappNodeData {
    label: string;
    nodeType: string;
    config: {
        whatsapp_account_id?: string;
        action?: 'send_message' | 'send_template';
        to?: string;
        message?: string;
        template_name?: string;
        template_language?: string;
    };
}

export function WhatsappNode({ data, selected }: NodeProps) {
    const nodeData = data as WhatsappNodeData;
    const action = nodeData.config.action ?? 'send_message';
    const hasConfig = nodeData.config.whatsapp_account_id && nodeData.config.to;

    return (
        <div
            className={`min-w-[180px] rounded-lg border-2 bg-gradient-to-br from-green-50 to-green-100 p-3 shadow-md transition-all dark:from-green-950 dark:to-green-900 ${
                selected ? 'border-green-500 ring-2 ring-green-300' : 'border-green-300 dark:border-green-700'
            }`}
        >
            <Handle type="target" position={Position.Left} className="!h-3 !w-3 !border-2 !border-green-500 !bg-white dark:!bg-green-900" />

            <div className="flex items-center gap-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-500 text-white">
                    <MessageCircle className="h-4 w-4" />
                </div>
                <div>
                    <div className="text-sm font-semibold text-green-900 dark:text-green-100">WhatsApp</div>
                    <div className="text-xs text-green-600 dark:text-green-400">
                        {action === 'send_template' ? 'Enviar plantilla' : 'Enviar mensaje'}
                    </div>
                </div>
            </div>

            {hasConfig && (
                <div className="mt-2 rounded bg-green-200/50 px-2 py-1 text-xs text-green-700 dark:bg-green-800/50 dark:text-green-300">
                    {action === 'send_template' && nodeData.config.template_name
                        ? `Plantilla: ${nodeData.config.template_name}`
                        : `A: ${nodeData.config.to?.substring(0, 15)}${(nodeData.config.to?.length ?? 0) > 15 ? '...' : ''}`}
                </div>
            )}

            <Handle type="source" position={Position.Right} className="!h-3 !w-3 !border-2 !border-green-500 !bg-white dark:!bg-green-900" />
        </div>
    );
}
