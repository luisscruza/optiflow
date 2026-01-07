import { Link } from '@inertiajs/react';
import { AlertTriangle, Clock, Eye, LayoutGrid } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface WorkflowSummary {
    id: string;
    name: string;
    is_active: boolean;
    pending_jobs_count: number;
    overdue_jobs_count: number;
}

interface WorkflowsSummaryWidgetProps {
    data: WorkflowSummary[];
    onRemove: () => void;
}

export function WorkflowsSummaryWidget({ data, onRemove }: WorkflowsSummaryWidgetProps) {
    return (
        <div className="bg-white group relative flex h-full flex-col p-5">
            {/* Remove button */}
            <button
                onClick={onRemove}
                className="absolute top-2 right-2 z-10 rounded-full p-1 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 dark:hover:bg-gray-700"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="h-4 w-4 text-gray-500">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            {/* Header */}
            <div className="mb-3 flex items-center gap-2 border-b-2 border-gray-300 pb-2 dark:border-gray-600">
                <LayoutGrid className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                <h3 className="text-base font-medium text-gray-600 dark:text-gray-400">Resumen de procesos</h3>
            </div>

            {/* Content */}
            {data.length === 0 ? (
                <div className="flex flex-1 items-center justify-center">
                    <p className="text-sm text-muted-foreground">No hay procesos configurados</p>
                </div>
            ) : (
                <div className="flex-1 space-y-2 overflow-y-auto pr-1">
                    {data.map((workflow) => {
                        const hasOverdue = workflow.overdue_jobs_count > 0;
                        const hasPending = workflow.pending_jobs_count > 0;

                        return (
                            <Link
                                key={workflow.id}
                                href={`/workflows/${workflow.id}`}
                                className="group/item block rounded-lg border border-gray-200 bg-white p-2.5 transition-all hover:border-gray-300 hover:shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {workflow.name}
                                            </p>
                                        </div>
                                        
                                        <div className="mt-1.5 flex items-center gap-3 text-xs">
                                            <div className={`flex items-center gap-1 ${hasPending ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'}`}>
                                                <Clock className="h-3 w-3" />
                                                <span className="font-medium">{workflow.pending_jobs_count}</span>
                                                <span>pendientes</span>
                                            </div>
                                            <div className={`flex items-center gap-1 ${hasOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'}`}>
                                                <AlertTriangle className="h-3 w-3" />
                                                <span className="font-medium">{workflow.overdue_jobs_count}</span>
                                                <span>vencidas</span>
                                            </div>
                                        </div>
                                    </div>

                                    <Eye className="h-4 w-4 shrink-0 text-gray-400 opacity-0 transition-opacity group-hover/item:opacity-100" />
                                </div>
                            </Link>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
