import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import * as React from 'react';

import { usePermissions, type Permission } from '@/hooks/use-permissions';

import { Button } from '../../button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '../../dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../tooltip';
import { type TableAction } from '../types';

import { DynamicIcon } from './dynamic-icon';

interface DataTableRowActionsProps<T> {
    actions: TableAction[];
    row: T;
    onActionClick: (action: TableAction, row: T) => void;
}

export function DataTableRowActions<T>({ actions, row, onActionClick }: DataTableRowActionsProps<T>) {
    const { can } = usePermissions();

    if (!actions || actions.length === 0) {
        return null;
    }

    const visibleActions = actions.filter((action) => {
        if (!action) return false;
        if (action.permission && !can(action.permission as Permission)) {
            return false;
        }
        return true;
    });

    if (visibleActions.length === 0) {
        return null;
    }

    const inlineActions = visibleActions.filter((a) => a.isInline);
    const dropdownActions = visibleActions.filter((a) => !a.isInline);

    return (
        <div className="flex items-center justify-end gap-1">
            {/* Inline actions as separate buttons */}
            {inlineActions.map((action) => {
                const button =
                    action.isCustom || action.name === 'delete' || !action.href || action.method === 'post' ? (
                        <Button
                            key={action.name}
                            variant="ghost"
                            size="sm"
                            onClick={() => onActionClick(action, row)}
                            className={action.color === 'danger' ? 'text-red-600 hover:text-red-700 dark:text-red-400' : undefined}
                        >
                            {action.icon && <DynamicIcon name={action.icon} className="h-4 w-4" />}
                        </Button>
                    ) : action.download ? (
                        <Button key={action.name} variant="ghost" size="sm" asChild>
                            <a href={action.href} target={action.target} download>
                                {action.icon && <DynamicIcon name={action.icon} className="h-4 w-4" />}
                            </a>
                        </Button>
                    ) : (
                        <Button key={action.name} variant="ghost" size="sm" asChild>
                            <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                {action.icon && <DynamicIcon name={action.icon} className="h-4 w-4" />}
                            </Link>
                        </Button>
                    );

                if (action.tooltip) {
                    return (
                        <Tooltip key={action.name}>
                            <TooltipTrigger asChild>{button}</TooltipTrigger>
                            <TooltipContent>{action.tooltip}</TooltipContent>
                        </Tooltip>
                    );
                }

                return button;
            })}

            {/* Dropdown actions if any */}
            {dropdownActions.length > 0 && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {dropdownActions.map((action) => {
                            if (action.isCustom || action.name === 'delete' || !action.href || action.method === 'post') {
                                return (
                                    <DropdownMenuItem
                                        key={action.name}
                                        onClick={() => onActionClick(action, row)}
                                        className={action.color === 'danger' ? 'text-red-600 dark:text-red-400' : undefined}
                                        title={action.tooltip}
                                    >
                                        {action.icon && <DynamicIcon name={action.icon} className="mr-2 h-4 w-4" />}
                                        <span className="flex flex-col">
                                            <span>{action.label}</span>
                                            {action.tooltip && (
                                                <span className="text-xs text-muted-foreground">{action.tooltip}</span>
                                            )}
                                        </span>
                                    </DropdownMenuItem>
                                );
                            }

                            if (action.download) {
                                return (
                                    <DropdownMenuItem key={action.name} asChild title={action.tooltip}>
                                        <a href={action.href} target={action.target} download>
                                            {action.icon && <DynamicIcon name={action.icon} className="mr-2 h-4 w-4" />}
                                            <span className="flex flex-col">
                                                <span>{action.label}</span>
                                                {action.tooltip && (
                                                    <span className="text-xs text-muted-foreground">{action.tooltip}</span>
                                                )}
                                            </span>
                                        </a>
                                    </DropdownMenuItem>
                                );
                            }

                            return (
                                <DropdownMenuItem key={action.name} asChild title={action.tooltip}>
                                    <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                        {action.icon && <DynamicIcon name={action.icon} className="mr-2 h-4 w-4" />}
                                        <span className="flex flex-col">
                                            <span>{action.label}</span>
                                            {action.tooltip && (
                                                <span className="text-xs text-muted-foreground">{action.tooltip}</span>
                                            )}
                                        </span>
                                    </Link>
                                </DropdownMenuItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}
        </div>
    );
}
