import { Handle, Position, type NodeProps } from '@xyflow/react';
import { Send } from 'lucide-react';

interface TelegramNodeData {
    label: string;
    nodeType: string;
    config: {
        bot_token?: string;
        chat_id?: string;
        message?: string;
        parse_mode?: 'HTML' | 'Markdown' | 'MarkdownV2';
        disable_notification?: boolean;
    };
}

export function TelegramNode({ data, selected }: NodeProps) {
    const nodeData = data as TelegramNodeData;
    const hasConfig = nodeData.config.chat_id && nodeData.config.message;

    return (
        <div
            className={`min-w-[180px] rounded-lg border-2 bg-gradient-to-br from-sky-50 to-sky-100 p-3 shadow-md transition-all dark:from-sky-950 dark:to-sky-900 ${
                selected ? 'border-sky-500 ring-2 ring-sky-300' : 'border-sky-300 dark:border-sky-700'
            }`}
        >
            <Handle type="target" position={Position.Left} className="!h-3 !w-3 !border-2 !border-sky-500 !bg-white dark:!bg-sky-900" />

            <div className="flex items-center gap-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-sky-500 text-white">
                    <Send className="h-4 w-4" />
                </div>
                <div>
                    <div className="text-sm font-semibold text-sky-900 dark:text-sky-100">Telegram</div>
                    <div className="text-xs text-sky-600 dark:text-sky-400">Enviar mensaje</div>
                </div>
            </div>

            {hasConfig && (
                <div className="mt-2 rounded bg-sky-200/50 px-2 py-1 text-xs text-sky-700 dark:bg-sky-800/50 dark:text-sky-300">
                    Chat: {nodeData.config.chat_id?.substring(0, 15)}
                    {nodeData.config.chat_id && nodeData.config.chat_id.length > 15 ? '...' : ''}
                </div>
            )}

            <Handle type="source" position={Position.Right} className="!h-3 !w-3 !border-2 !border-sky-500 !bg-white dark:!bg-sky-900" />
        </div>
    );
}
