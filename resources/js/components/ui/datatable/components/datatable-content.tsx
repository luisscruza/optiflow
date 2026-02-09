import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import * as React from 'react';
import { toast } from 'sonner';

import { cn } from '@/lib/utils';

import { Badge } from '../../badge';
import { Checkbox } from '../../checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../table';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../tooltip';
import { type RenderCellFn, type StatusConfig, type TableAction, type TableColumn } from '../types';

import { DataTableRowActions } from './datatable-row-actions';

interface DataTableContentProps<T> {
    data: T[];
    columns: TableColumn[];
    selectable?: boolean;
    rowHref?: string | null;
    emptyMessage: string;
    emptyState?: React.ReactNode;
    currentSortBy: string | null;
    currentSortDirection: 'asc' | 'desc';
    onSort: (columnKey: string) => void;
    isAllSelected: boolean;
    onToggleSelectAll: () => void;
    getRowKey: (row: T, index: number) => string | number;
    isRowSelected: (rowKey: string | number) => boolean;
    onToggleSelectRow: (rowKey: string | number) => void;
    rowActions?: (row: T) => React.ReactNode;
    formatCurrency: (value: number) => string;
    onActionClick: (action: TableAction, row: T) => void;
    renderCell?: RenderCellFn<T>;
}

function fallbackCopyToClipboard(text: string): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';

    document.body.appendChild(textarea);
    textarea.select();

    const didCopy = document.execCommand('copy');
    document.body.removeChild(textarea);

    return didCopy;
}

export function DataTableContent<T>({
    data,
    columns,
    selectable,
    rowHref,
    emptyMessage,
    emptyState,
    currentSortBy,
    currentSortDirection,
    onSort,
    isAllSelected,
    onToggleSelectAll,
    getRowKey,
    isRowSelected,
    onToggleSelectRow,
    rowActions,
    formatCurrency,
    onActionClick,
    renderCell,
}: DataTableContentProps<T>) {
    const resolveCopyValue = React.useCallback((row: T, column: TableColumn): string | null => {
        if (!column.copiable || column.type === 'actions') {
            return null;
        }

        const rowData = row as Record<string, unknown>;
        const copyValue = rowData[`${column.key}_copy`] ?? rowData[column.key];

        if (copyValue === null || copyValue === undefined) {
            return null;
        }

        if (typeof copyValue === 'string' || typeof copyValue === 'number' || typeof copyValue === 'boolean') {
            return String(copyValue);
        }

        return null;
    }, []);

    const handleCopyToClipboard = React.useCallback(async (text: string): Promise<void> => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
            } else if (!fallbackCopyToClipboard(text)) {
                throw new Error('Clipboard API unavailable.');
            }

            toast.success('Copiado al portapapeles.');
        } catch {
            if (fallbackCopyToClipboard(text)) {
                toast.success('Copiado al portapapeles.');

                return;
            }

            toast.error('No se pudo copiar el valor.');
        }
    }, []);

    const renderCellContent = React.useCallback(
        (row: T, column: TableColumn): React.ReactNode => {
            const rowData = row as Record<string, unknown>;
            const value = rowData[column.key];
            const href = rowData[`${column.key}_href`] as string | undefined;

            // Try custom renderer first
            if (renderCell) {
                const customContent = renderCell(column.key, value, row);
                if (customContent !== undefined) {
                    // If custom renderer returns content, use it
                    if (href && column.type !== 'actions' && !column.copiable) {
                        return (
                            <Link href={href} className="font-medium text-primary hover:underline">
                                {customContent}
                            </Link>
                        );
                    }
                    return customContent;
                }
            }

            let content: React.ReactNode;

            switch (column.type) {
                case 'currency':
                    content = formatCurrency(Number(value));
                    break;
                case 'number':
                    content = new Intl.NumberFormat('es-DO').format(Number(value));
                    break;
                case 'badge': {
                    const statusConfig = value as StatusConfig;
                    content = (
                        <Badge
                            variant={statusConfig?.variant as 'default' | 'destructive' | 'outline' | 'secondary'}
                            className={statusConfig?.className}
                        >
                            {statusConfig?.label}
                        </Badge>
                    );
                    break;
                }
                case 'date':
                    content = value ? String(value) : '-';
                    break;
                case 'actions': {
                    const actions = value as TableAction[] | undefined;
                    return <DataTableRowActions actions={actions || []} row={row} onActionClick={onActionClick} />;
                }
                default:
                    content = value !== null && value !== undefined ? String(value) : '-';
            }

            if (href && column.type !== 'actions' && !column.copiable) {
                return (
                    <Link href={href} className="font-medium text-primary hover:underline">
                        {content}
                    </Link>
                );
            }


            return content;
        },
        [formatCurrency, onActionClick, renderCell],
    );

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {selectable && (
                        <TableHead className="w-[50px]">
                            <Checkbox checked={isAllSelected} onCheckedChange={onToggleSelectAll} aria-label="Seleccionar todo" />
                        </TableHead>
                    )}
                    {columns.map((column) => {
                        const header = (
                            <TableHead
                                key={column.key}
                                className={cn(
                                    'group',
                                    column.align === 'right' && 'text-right',
                                    column.align === 'center' && 'text-center',
                                    column.sortable && 'cursor-pointer select-none hover:bg-muted/50',
                                    column.headerClassName,
                                )}
                                onClick={() => column.sortable && onSort(column.key)}
                            >
                                <div
                                    className={cn(
                                        'flex items-center gap-1',
                                        column.align === 'right' && 'justify-end',
                                        column.align === 'center' && 'justify-center',
                                    )}
                                >
                                    {column.label}
                                    {column.sortable && (
                                        <span className="ml-1">
                                            {currentSortBy === column.key ? (
                                                currentSortDirection === 'asc' ? (
                                                    <ArrowUp className="h-4 w-4" />
                                                ) : (
                                                    <ArrowDown className="h-4 w-4" />
                                                )
                                            ) : (
                                                <ArrowUpDown className="h-4 w-4 text-muted-foreground/50 opacity-0 transition-opacity group-hover:opacity-100" />
                                            )}
                                        </span>
                                    )}
                                </div>
                            </TableHead>
                        );

                        if (column.tooltip) {
                            return (
                                <Tooltip key={column.key}>
                                    <TooltipTrigger asChild>{header}</TooltipTrigger>
                                    <TooltipContent>{column.tooltip}</TooltipContent>
                                </Tooltip>
                            );
                        }

                        return header;
                    })}
                    {rowActions && <TableHead className="text-right">Acciones</TableHead>}
                </TableRow>
            </TableHeader>
            <TableBody>
                {data.length > 0 ? (
                    data.map((row, index) => {
                        const rowData = row as Record<string, unknown>;
                        const href = rowHref ? rowHref.replace(/\{(\w+)\}/g, (_, key) => String(rowData[key] ?? '')) : null;
                        const rowKey = getRowKey(row, index);
                        const isSelected = isRowSelected(rowKey);

                        return (
                            <TableRow
                                key={rowKey}
                                className={cn(
                                    href && 'cursor-pointer',
                                    href && !isSelected && 'hover:bg-gray-50 dark:hover:bg-gray-800/50',
                                    isSelected && 'bg-teal-50 dark:bg-teal-950/20',
                                )}
                                onClick={
                                    href
                                        ? (e) => {
                                              const target = e.target as HTMLElement;
                                              if (
                                                  target.closest('button') ||
                                                  target.closest('a') ||
                                                  target.closest('input') ||
                                                  target.closest('[role="button"]')
                                              ) {
                                                  return;
                                              }
                                              router.visit(href);
                                          }
                                        : undefined
                                }
                            >
                                {selectable && (
                                    <TableCell className="w-[50px]">
                                        <Checkbox
                                            checked={isSelected}
                                            onCheckedChange={() => onToggleSelectRow(rowKey)}
                                            aria-label={`Seleccionar fila ${index + 1}`}
                                        />
                                    </TableCell>
                                )}
                                {columns.map((column) => {
                                    const rowData = row as Record<string, unknown>;
                                    const cellTooltip = rowData[`${column.key}_tooltip`] as string | undefined;
                                    const cellContent = renderCellContent(row, column);
                                    const copyValue = resolveCopyValue(row, column);
                                    const copyableContent = copyValue ? (
                                        <button
                                            type="button"
                                            className="cursor-copy text-left focus-visible:outline-none"
                                            onClick={(event) => {
                                                event.preventDefault();
                                                event.stopPropagation();
                                                void handleCopyToClipboard(copyValue);
                                            }}
                                        >
                                            {cellContent}
                                        </button>
                                    ) : (
                                        cellContent
                                    );

                                    return (
                                        <TableCell
                                            key={column.key}
                                            className={cn(
                                                column.align === 'right' && 'text-right',
                                                column.align === 'center' && 'text-center',
                                                column.cellClassName,
                                            )}
                                        >
                                            {cellTooltip ? (
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        {copyValue ? copyableContent : <div className="cursor-help">{copyableContent}</div>}
                                                    </TooltipTrigger>
                                                    <TooltipContent>{cellTooltip}</TooltipContent>
                                                </Tooltip>
                                            ) : (
                                                copyableContent
                                            )}
                                        </TableCell>
                                    );
                                })}
                                {rowActions && <TableCell className="text-right">{rowActions(row)}</TableCell>}
                            </TableRow>
                        );
                    })
                ) : (
                    <TableRow>
                        <TableCell colSpan={columns.length + (rowActions ? 1 : 0) + (selectable ? 1 : 0)} className="h-24 text-center">
                            {emptyState || emptyMessage}
                        </TableCell>
                    </TableRow>
                )}
            </TableBody>
        </Table>
    );
}
