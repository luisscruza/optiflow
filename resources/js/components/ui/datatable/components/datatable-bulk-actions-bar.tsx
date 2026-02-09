import * as React from 'react';

import { usePermissions, type Permission } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';

import { Button } from '../../button';
import { type BulkAction } from '../types';

import { DynamicIcon } from './dynamic-icon';

interface DataTableBulkActionsBarProps {
    selectedCount: number;
    bulkActions: BulkAction[];
    onClearSelection: () => void;
    onBulkAction: (action: BulkAction) => void;
    isBulkProcessing: boolean;
}

export const DataTableBulkActionsBar: React.FC<DataTableBulkActionsBarProps> = ({
    selectedCount,
    bulkActions,
    onClearSelection,
    onBulkAction,
    isBulkProcessing,
}) => {
    const { can } = usePermissions();

    return (
        <div className="flex items-center justify-between border-b bg-yellow-50 p-4 dark:bg-yellow-950/20">
            <div className="flex items-center gap-4">
                <span className="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                    {selectedCount} seleccionado{selectedCount !== 1 ? 's' : ''}
                </span>
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={onClearSelection}
                    className="h-7 text-xs text-yellow-700 hover:text-yellow-900 dark:text-yellow-300 dark:hover:text-yellow-100"
                >
                    Limpiar selección
                </Button>
            </div>
            <div className="flex items-center gap-2">
                {bulkActions
                    .filter((action) => !action.permission || can(action.permission as Permission))
                    .map((action) => (
                        <Button
                            key={action.name}
                            variant={action.color === 'danger' ? 'destructive' : 'default'}
                            size="sm"
                            onClick={() => onBulkAction(action)}
                            disabled={isBulkProcessing}
                            className={cn(
                                'gap-2',
                                action.color === 'primary' && 'bg-yellow-600 text-white hover:bg-yellow-700',
                            )}
                        >
                            {action.icon && <DynamicIcon name={action.icon} className="h-4 w-4" />}
                            {action.label}
                        </Button>
                    ))}
            </div>
        </div>
    );
};
