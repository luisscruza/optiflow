'use client';

import { Link, router } from '@inertiajs/react';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronDown, DollarSign, Download, Edit, Eye, FileText, Filter, MoreHorizontal, Printer, Search, Send, Trash2, X, XCircle } from 'lucide-react';
import * as React from 'react';
import { useCallback, useState } from 'react';
import { DateRange } from 'react-day-picker';

import { usePermissions, type Permission } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import { type PaginatedResponse } from '@/types';
import { useCurrency } from '@/utils/currency';

import { Badge } from './badge';
import { Button } from './button';
import { Card, CardContent } from './card';
import { Checkbox } from './checkbox';
import { DateRangePicker } from './date-range-picker';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from './dropdown-menu';
import { Input } from './input';
import { Label } from './label';
import { Paginator } from './paginator';
import { Popover, PopoverContent, PopoverTrigger } from './popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './table';
import { Tooltip, TooltipContent, TooltipTrigger } from './tooltip';

// ============================================================================
// Types
// ============================================================================

export interface TableColumn {
    key: string;
    label: string;
    type: string;
    sortable: boolean;
    align?: 'left' | 'center' | 'right';
    headerClassName?: string;
    cellClassName?: string;
    tooltip?: string;
}

export interface TableFilter {
    name: string;
    label: string;
    type: 'search' | 'select' | 'date';
    default?: unknown;
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
    hidden?: boolean;
    inline?: boolean;
}

export interface TableAction {
    name: string;
    label: string;
    icon?: string;
    href?: string;
    color?: string;
    requiresConfirmation?: boolean;
    confirmationMessage?: string;
    permission?: string;
    isCustom?: boolean;
    handler?: string;
    isInline?: boolean;
    target?: string;
    prefetch?: boolean;
    tooltip?: string;
    download?: boolean;
}

export interface BulkAction {
    name: string;
    label: string;
    icon?: string;
    color?: string;
    requiresConfirmation?: boolean;
    confirmationMessage?: string;
    permission?: string;
    handler?: string;
    href?: string;
}

export interface TableResource<T = Record<string, unknown>> {
    data: PaginatedResponse<T>;
    columns: TableColumn[];
    filters: TableFilter[];
    appliedFilters: Record<string, string>;
    sortBy: string | null;
    sortDirection: 'asc' | 'desc';
    perPage: number;
    perPageOptions: number[];
    rowHref?: string | null;
    selectable?: boolean;
    bulkActions?: BulkAction[];
}

export interface DataTableProps<T = Record<string, unknown>> {
    /** The table resource from the backend */
    resource: TableResource<T>;
    /** The base URL for filtering/sorting/pagination */
    baseUrl: string;
    /** Enable export functionality */
    exportUrl?: string;
    /** Custom empty state message */
    emptyMessage?: string;
    /** Custom empty state component */
    emptyState?: React.ReactNode;
    /** Additional class name */
    className?: string;
    /** Row key extractor */
    getRowKey?: (row: T, index: number) => string | number;
    /** Custom row actions (overrides column actions) */
    rowActions?: (row: T) => React.ReactNode;
    /** Custom header actions */
    headerActions?: React.ReactNode;
    /** Title for active filters section */
    activeFiltersTitle?: string;
    /** Label for "All" option in select filters */
    allLabel?: string;
    /** Label for clear filters button */
    clearFiltersLabel?: string;
    /** Label for export button */
    exportLabel?: string;
    /** Label for more filters button */
    moreFiltersLabel?: string;
    /** Debounce delay for search (ms) */
    searchDebounce?: number;
    /** Callback when action is triggered */
    onAction?: (action: string, row: T) => void;
    /** Handler functions for custom actions */
    handlers?: Record<string, (row: T) => void>;
    /** Handler functions for bulk actions */
    bulkHandlers?: Record<string, (selectedIds: (string | number)[]) => void>;
}

interface StatusConfig {
    value: string;
    label: string;
    variant: string;
    className?: string;
}

// ============================================================================
// Icon mapping
// ============================================================================

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    eye: Eye,
    edit: Edit,
    trash: Trash2,
    dollar: DollarSign,
    download: Download,
    printer: Printer,
    send: Send,
    file: FileText,
    cancel: XCircle,
};

// ============================================================================
// DataTable Component
// ============================================================================

export function DataTable<T = Record<string, unknown>>({
    resource,
    baseUrl,
    exportUrl,
    emptyMessage = 'No hay datos disponibles.',
    emptyState,
    className,
    getRowKey = (row, index) => ((row as Record<string, unknown>).id as number) ?? index,
    rowActions,
    headerActions,
    activeFiltersTitle = 'Filtros activos:',
    allLabel = 'Todos',
    clearFiltersLabel = 'Limpiar filtros',
    exportLabel = 'Exportar',
    moreFiltersLabel = 'Más filtros',
    searchDebounce = 500,
    onAction,
    handlers = {},
    bulkHandlers = {},
}: DataTableProps<T>) {
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();
    const { data, columns, filters, appliedFilters, sortBy, sortDirection, rowHref, selectable, bulkActions = [] } = resource;

    const [filterValues, setFilterValues] = useState<Record<string, string>>(appliedFilters);
    const [searchQuery, setSearchQuery] = useState(appliedFilters.search || '');
    const [currentSortBy, setCurrentSortBy] = useState<string | null>(sortBy);
    const [currentSortDirection, setCurrentSortDirection] = useState<'asc' | 'desc'>(sortDirection);
    const searchTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);

    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);
    const isAllSelected = data.data.length > 0 && selectedIds.length === data.data.length;
    const isSomeSelected = selectedIds.length > 0 && selectedIds.length < data.data.length;
    const hasSelection = selectedIds.length > 0;

    // Get filters by type and visibility
    const searchFilter = filters.find((f) => f.type === 'search');
    const inlineSelectFilters = filters.filter((f) => f.type === 'select' && f.inline && !f.hidden);
    const popoverSelectFilters = filters.filter((f) => f.type === 'select' && !f.inline && !f.hidden);
    const hiddenFilters = filters.filter((f) => f.type === 'select' && f.hidden);
    // Check for any date range filters (fields ending with _start and _end)
    const dateRangeFilters = filters.filter((f) => f.type === 'date' && f.name.endsWith('_start'));
    const inlineDateRangeFilters = dateRangeFilters.filter((f) => f.inline);
    const popoverDateRangeFilters = dateRangeFilters.filter((f) => !f.inline);
    const hasDateRangeFilter = dateRangeFilters.length > 0;

    // Initialize date range from applied filters (support dynamic date range filter names)
    const getInitialDateRange = useCallback((): DateRange | undefined => {
        if (dateRangeFilters.length === 0) return undefined;
        
        const dateRangeFilter = dateRangeFilters[0];
        const startName = dateRangeFilter.name;
        const endName = startName.replace('_start', '_end');
        
        const start = appliedFilters[startName];
        const end = appliedFilters[endName];
        if (start || end) {
            return {
                from: start ? parseISO(start) : undefined,
                to: end ? parseISO(end) : undefined,
            };
        }
        return undefined;
    }, [appliedFilters, dateRangeFilters]);

    const [dateRange, setDateRange] = useState<DateRange | undefined>(getInitialDateRange());

    // Confirmation dialog state
    const [confirmationDialog, setConfirmationDialog] = useState<{
        open: boolean;
        action: TableAction | null;
        row: T | null;
    }>({ open: false, action: null, row: null });
    const [isDeleting, setIsDeleting] = useState(false);

    // Bulk action confirmation dialog state
    const [bulkConfirmationDialog, setBulkConfirmationDialog] = useState<{
        open: boolean;
        action: BulkAction | null;
    }>({ open: false, action: null });
    const [isBulkProcessing, setIsBulkProcessing] = useState(false);

    const navigateWithFilters = useCallback(
        (newFilters: Record<string, string>, options?: { sortBy?: string; sortDirection?: 'asc' | 'desc' }) => {
            const params: Record<string, string> = { ...newFilters };
            if (options?.sortBy) {
                params.sort_by = options.sortBy;
                params.sort_direction = options.sortDirection || 'asc';
            } else if (currentSortBy) {
                params.sort_by = currentSortBy;
                params.sort_direction = currentSortDirection;
            }
            router.get(baseUrl, params, { preserveState: true, preserveScroll: true });
        },
        [baseUrl, currentSortBy, currentSortDirection],
    );

    const handleSort = useCallback(
        (columnKey: string) => {
            const column = columns.find((c) => c.key === columnKey);
            if (!column?.sortable) return;

            let newDirection: 'asc' | 'desc' = 'asc';
            if (currentSortBy === columnKey) {
                newDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            }

            setCurrentSortBy(columnKey);
            setCurrentSortDirection(newDirection);

            navigateWithFilters(filterValues, { sortBy: columnKey, sortDirection: newDirection });
        },
        [columns, currentSortBy, currentSortDirection, filterValues, navigateWithFilters],
    );

    const handleFilterChange = useCallback(
        (name: string, value: string) => {
            const newFilters = { ...filterValues, [name]: value };
            setFilterValues(newFilters);
            navigateWithFilters(newFilters);
        },
        [filterValues, navigateWithFilters],
    );

    const handleSearch = useCallback(
        (value: string) => {
            setSearchQuery(value);

            if (searchTimeoutRef.current) {
                clearTimeout(searchTimeoutRef.current);
            }

            searchTimeoutRef.current = setTimeout(() => {
                const newFilters = { ...filterValues, search: value };
                setFilterValues(newFilters);
                navigateWithFilters(newFilters);
            }, searchDebounce);
        },
        [filterValues, navigateWithFilters, searchDebounce],
    );

    const handleDateRangeChange = useCallback(
        (range: DateRange | undefined) => {
            if (dateRangeFilters.length === 0) return;
            
            const dateRangeFilter = dateRangeFilters[0];
            const startName = dateRangeFilter.name;
            const endName = startName.replace('_start', '_end');
            
            setDateRange(range);
            const newFilters = {
                ...filterValues,
                [startName]: range?.from ? format(range.from, 'yyyy-MM-dd') : '',
                [endName]: range?.to ? format(range.to, 'yyyy-MM-dd') : '',
            };
            setFilterValues(newFilters);
            navigateWithFilters(newFilters);
        },
        [filterValues, navigateWithFilters, dateRangeFilters],
    );

    const handleExport = useCallback(() => {
        if (!exportUrl) return;
        const params = new URLSearchParams();
        Object.entries(filterValues).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });
        window.location.href = `${exportUrl}?${params.toString()}`;
    }, [exportUrl, filterValues]);

    const getActiveFilters = useCallback(() => {
        const active: Array<{ name: string; label: string; displayValue: string; isDateRange?: boolean }> = [];
        const processedFilters = new Set<string>();

        filters.forEach((filter) => {
            if (processedFilters.has(filter.name)) {
                return;
            }

            const value = filterValues[filter.name];
            const defaultValue = filter.default;

            if (!value || value === defaultValue || value === 'all' || value === '') {
                return;
            }

            let displayValue = '';

            if (filter.type === 'search') {
                displayValue = `"${value}"`;
            } else if (filter.type === 'date') {
                // Check if this is part of a date range (ends with _start)
                if (filter.name.endsWith('_start')) {
                    const startName = filter.name;
                    const endName = filter.name.replace('_start', '_end');
                    const startDate = filterValues[startName];
                    const endDate = filterValues[endName];
                    const startFilter = filters.find((f) => f.name === startName);
                    const endFilter = filters.find((f) => f.name === endName);
                    const startDefault = startFilter?.default;
                    const endDefault = endFilter?.default;

                    if (startDate && endDate && (startDate !== startDefault || endDate !== endDefault)) {
                        displayValue = `${format(parseISO(startDate), 'd MMM yyyy')} - ${format(parseISO(endDate), 'd MMM yyyy')}`;
                        active.push({
                            name: startName,
                            label: filter.label,
                            displayValue,
                            isDateRange: true,
                        });
                        processedFilters.add(startName);
                        processedFilters.add(endName);
                    }
                    return;
                } else if (filter.name.endsWith('_end')) {
                    // Skip _end filters, they're processed with _start
                    return;
                }
                displayValue = format(parseISO(value), 'd MMM yyyy');
            } else if (filter.type === 'select') {
                const option = filter.options?.find((opt) => opt.value === value);
                displayValue = option?.label || String(value);
            } else {
                displayValue = String(value);
            }

            active.push({
                name: filter.name,
                label: filter.label,
                displayValue,
            });
        });

        return active;
    }, [filters, filterValues]);

    const handleRemoveFilter = useCallback(
        (filterName: string) => {
            const newFilters = { ...filterValues };

            // Check if this is a date range filter (ends with _start)
            if (filterName.endsWith('_start')) {
                const startName = filterName;
                const endName = filterName.replace('_start', '_end');
                const startDateFilter = filters.find((f) => f.name === startName);
                const endDateFilter = filters.find((f) => f.name === endName);
                newFilters[startName] = (startDateFilter?.default as string) || '';
                newFilters[endName] = (endDateFilter?.default as string) || '';
                setDateRange(undefined);
            } else {
                const filter = filters.find((f) => f.name === filterName);
                const defaultValue = filter?.default || 'all';
                newFilters[filterName] = defaultValue as string;

                if (filterName === 'search') {
                    setSearchQuery('');
                }
            }

            setFilterValues(newFilters);
            navigateWithFilters(newFilters);
        },
        [filterValues, filters, navigateWithFilters],
    );

    const handleClearFilters = useCallback(() => {
        const defaultFilters: Record<string, string> = {};
        filters.forEach((filter) => {
            defaultFilters[filter.name] = (filter.default as string) || '';
        });
        setFilterValues(defaultFilters);
        setSearchQuery('');
        setDateRange(undefined);
        navigateWithFilters(defaultFilters);
    }, [filters, navigateWithFilters]);

    const handleActionClick = useCallback(
        (action: TableAction, row: T) => {
            if (action.requiresConfirmation) {
                setConfirmationDialog({ open: true, action, row });
                return;
            }

            // Handle custom actions with registered handler
            if (action.handler && handlers[action.handler]) {
                handlers[action.handler](row);
                return;
            }

            // Handle custom actions via callback
            if (action.isCustom && onAction) {
                onAction(action.name, row);
                return;
            }

            if (action.name === 'delete' && action.href) {
                router.delete(action.href);
                return;
            }

            if (onAction) {
                onAction(action.name, row);
            }
        },
        [onAction, handlers],
    );

    const handleConfirmAction = useCallback(() => {
        const { action, row } = confirmationDialog;
        if (!action || !row) return;

        // Handle custom actions with registered handler
        if (action.handler && handlers[action.handler]) {
            handlers[action.handler](row);
            setConfirmationDialog({ open: false, action: null, row: null });
            return;
        }

        // Handle custom actions with confirmation
        if (action.isCustom && onAction) {
            onAction(action.name, row);
            setConfirmationDialog({ open: false, action: null, row: null });
            return;
        }

        if (action.name === 'delete' && action.href) {
            setIsDeleting(true);
            router.delete(action.href, {
                preserveScroll: true,
                onSuccess: () => {
                    setConfirmationDialog({ open: false, action: null, row: null });
                    setIsDeleting(false);
                },
                onError: () => {
                    setIsDeleting(false);
                },
            });
            return;
        }

        if (onAction) {
            onAction(action.name, row);
        }
        setConfirmationDialog({ open: false, action: null, row: null });
    }, [confirmationDialog, onAction, handlers]);

    // Selection handlers
    const toggleSelectAll = useCallback(() => {
        if (isAllSelected) {
            setSelectedIds([]);
        } else {
            setSelectedIds(data.data.map((row: T, index: number) => getRowKey(row, index)));
        }
    }, [data.data, getRowKey, isAllSelected]);

    const toggleSelectRow = useCallback(
        (rowKey: string | number) => {
            setSelectedIds((prev) =>
                prev.includes(rowKey) ? prev.filter((id) => id !== rowKey) : [...prev, rowKey],
            );
        },
        [],
    );

    const clearSelection = useCallback(() => {
        setSelectedIds([]);
    }, []);

    // Bulk action handlers
    const executeBulkAction = useCallback(
        (action: BulkAction) => {
            // Handle custom bulk actions with registered handler
            if (action.handler && bulkHandlers[action.handler]) {
                setIsBulkProcessing(true);
                Promise.resolve(bulkHandlers[action.handler](selectedIds)).finally(() => {
                    setIsBulkProcessing(false);
                    clearSelection();
                    setBulkConfirmationDialog({ open: false, action: null });
                });
                return;
            }

            // Handle bulk actions with href
            if (action.href) {
                setIsBulkProcessing(true);
                
                axios.post(action.href, { ids: selectedIds }, { 
                    responseType: 'blob',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                })
                    .then((response) => {
                        // Extract filename from Content-Disposition header
                        const contentDisposition = response.headers['content-disposition'];
                        let filename = 'download.zip';
                        
                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
                            if (filenameMatch && filenameMatch[1]) {
                                filename = filenameMatch[1];
                            }
                        }
                        
                        // Create a download link and trigger it
                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);
                        
                        setIsBulkProcessing(false);
                        clearSelection();
                        setBulkConfirmationDialog({ open: false, action: null });
                    })
                    .catch((error) => {
                        console.error('Bulk action error:', error);
                        setIsBulkProcessing(false);
                        setBulkConfirmationDialog({ open: false, action: null });
                    });
            }
        },
        [bulkHandlers, selectedIds, clearSelection],
    );

    const handleBulkAction = useCallback(
        (action: BulkAction) => {
            if (action.requiresConfirmation) {
                setBulkConfirmationDialog({ open: true, action });
                return;
            }
            executeBulkAction(action);
        },
        [executeBulkAction],
    );

    const handleConfirmBulkAction = useCallback(() => {
        const { action } = bulkConfirmationDialog;
        if (!action) return;
        executeBulkAction(action);
    }, [bulkConfirmationDialog, executeBulkAction]);

    const renderCellContent = useCallback(
        (row: T, column: TableColumn) => {
            const rowData = row as Record<string, unknown>;
            const value = rowData[column.key];
            const href = rowData[`${column.key}_href`] as string | undefined;
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

                    // Separate inline actions from dropdown actions
                    const inlineActions = visibleActions.filter((a) => a.isInline);
                    const dropdownActions = visibleActions.filter((a) => !a.isInline);

                    content = (
                        <div className="flex items-center justify-end gap-1">
                            {/* Inline actions as separate buttons */}
                            {inlineActions.map((action) => {
                                const IconComponent = action.icon ? iconMap[action.icon] : null;
                                const button = action.isCustom || action.name === 'delete' || !action.href ? (
                                    <Button
                                        key={action.name}
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => handleActionClick(action, row)}
                                        className={action.color === 'danger' ? 'text-red-600 hover:text-red-700 dark:text-red-400' : undefined}
                                    >
                                        {IconComponent && <IconComponent className="h-4 w-4" />}
                                    </Button>
                                ) : action.download ? (
                                    <Button key={action.name} variant="ghost" size="sm" asChild>
                                        <a href={action.href} target={action.target} download>
                                            {IconComponent && <IconComponent className="h-4 w-4" />}
                                        </a>
                                    </Button>
                                ) : (
                                    <Button key={action.name} variant="ghost" size="sm" asChild>
                                        <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                            {IconComponent && <IconComponent className="h-4 w-4" />}
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
                                            const IconComponent = action.icon ? iconMap[action.icon] : null;

                                            // Custom actions or delete actions use onClick
                                            if (action.isCustom || action.name === 'delete' || !action.href) {
                                                return (
                                                    <DropdownMenuItem
                                                        key={action.name}
                                                        onClick={() => handleActionClick(action, row)}
                                                        className={action.color === 'danger' ? 'text-red-600 dark:text-red-400' : undefined}
                                                        title={action.tooltip}
                                                    >
                                                        {IconComponent && <IconComponent className="mr-2 h-4 w-4" />}
                                                        <span className="flex flex-col">
                                                            <span>{action.label}</span>
                                                            {action.tooltip && (
                                                                <span className="text-xs text-muted-foreground">{action.tooltip}</span>
                                                            )}
                                                        </span>
                                                    </DropdownMenuItem>
                                                );
                                            }

                                            // Download actions use regular anchor tags
                                            if (action.download) {
                                                return (
                                                    <DropdownMenuItem key={action.name} asChild title={action.tooltip}>
                                                        <a href={action.href} target={action.target} download>
                                                            {IconComponent && <IconComponent className="mr-2 h-4 w-4" />}
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

                                            // Regular actions with href use Link
                                            return (
                                                <DropdownMenuItem key={action.name} asChild title={action.tooltip}>
                                                    <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                                        {IconComponent && <IconComponent className="mr-2 h-4 w-4" />}
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
                    break;
                }
                default:
                    content = value !== null && value !== undefined ? String(value) : '-';
            }

            // Wrap in link if href exists and type is not actions
            if (href && column.type !== 'actions') {
                return (
                    <Link href={href} className="font-medium text-primary hover:underline">
                        {content}
                    </Link>
                );
            }

            return content;
        },
        [formatCurrency, can, handleActionClick],
    );

    const activeFilters = getActiveFilters();
    const hasActiveFilters = activeFilters.length > 0;
    const allPopoverSelectFilters = [...popoverSelectFilters, ...hiddenFilters];
    const hasPopoverFilters = allPopoverSelectFilters.length > 0 || popoverDateRangeFilters.length > 0;
    const hasInlineFilters = inlineSelectFilters.length > 0 || inlineDateRangeFilters.length > 0;

    return (
        <div className={cn('space-y-4', className)}>
            {/* Header Actions */}
            {headerActions && (
                <div className="flex items-center gap-2">
                    {headerActions}

                    {/* Export Button */}
                    {exportUrl && (
                        <Button variant="outline" onClick={handleExport} className="gap-2">
                            <Download className="h-4 w-4" />
                            {exportLabel}
                        </Button>
                    )}
                </div>
            )}

            {/* Data Table Card */}
            <Card>
                <CardContent className="p-0">
                    {/* Bulk Actions Bar */}
                    {hasSelection ? (
                        <div className="flex items-center justify-between border-b bg-teal-50 p-4 dark:bg-teal-950/20">
                            <div className="flex items-center gap-4">
                                <span className="text-sm font-medium text-teal-900 dark:text-teal-100">
                                    {selectedIds.length} seleccionado{selectedIds.length !== 1 ? 's' : ''}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearSelection}
                                    className="h-7 text-xs text-teal-700 hover:text-teal-900 dark:text-teal-300 dark:hover:text-teal-100"
                                >
                                    Limpiar selección
                                </Button>
                            </div>
                            <div className="flex items-center gap-2">
                                {bulkActions
                                    .filter((action) => !action.permission || can(action.permission as Permission))
                                    .map((action) => {
                                        const Icon = iconMap[action.icon || 'circle'];
                                        return (
                                            <Button
                                                key={action.name}
                                                variant={action.color === 'danger' ? 'destructive' : 'default'}
                                                size="sm"
                                                onClick={() => handleBulkAction(action)}
                                                disabled={isBulkProcessing}
                                                className={cn(
                                                    'gap-2',
                                                    action.color === 'primary' && 'bg-teal-600 hover:bg-teal-700 text-white',
                                                )}
                                            >
                                                <Icon className="h-4 w-4" />
                                                {action.label}
                                            </Button>
                                        );
                                    })}
                            </div>
                        </div>
                    ) : (
                        <>
                            {/* Table Header with Search and Filters */}
                            <div className="flex flex-wrap items-center gap-3 border-b p-4">
                        {/* Search */}
                        {searchFilter && (
                            <div className="relative max-w-xs flex-1">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    placeholder={searchFilter.placeholder || searchFilter.label}
                                    className="pl-9"
                                    value={searchQuery}
                                    onChange={(e) => handleSearch(e.target.value)}
                                />
                            </div>
                        )}

                        {/* Inline Date Range Filters */}
                        {inlineDateRangeFilters.map((filter) => (
                            <DateRangePicker
                                key={filter.name}
                                value={dateRange}
                                onChange={handleDateRangeChange}
                                placeholder={filter.placeholder || filter.label}
                            />
                        ))}

                        {/* Inline Select Filters */}
                        {inlineSelectFilters.map((filter) => (
                            <Select
                                key={filter.name}
                                value={filterValues[filter.name] || ''}
                                onValueChange={(value) => handleFilterChange(filter.name, value)}
                            >
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder={filter.label} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{allLabel}</SelectItem>
                                    {filter.options?.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ))}

                        {/* Filters Button */}
                        {hasPopoverFilters && (
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button variant="outline" className="gap-2">
                                        <Filter className="h-4 w-4" />
                                        Filtrar
                                        {hasActiveFilters && (
                                            <Badge variant="secondary" className="ml-1 h-5 min-w-5 rounded-full px-1.5">
                                                {activeFilters.length}
                                            </Badge>
                                        )}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-80" align="end">
                                    <div className="grid gap-4">
                                        <div className="space-y-2">
                                            <h4 className="font-medium leading-none">Filtrar Por</h4>
                                            <p className="text-sm text-muted-foreground">Configura los filtros para la tabla.</p>
                                        </div>
                                        <div className="grid gap-4">
                                            {/* Popover Date Range Filters */}
                                            {popoverDateRangeFilters.map((filter) => (
                                                <div key={filter.name} className="grid gap-2">
                                                    <Label>{filter.label}</Label>
                                                    <DateRangePicker
                                                        value={dateRange}
                                                        onChange={handleDateRangeChange}
                                                        placeholder="Seleccionar fechas"
                                                    />
                                                </div>
                                            ))}

                                            {/* Popover Select Filters */}
                                            {allPopoverSelectFilters.map((filter) => (
                                                <div key={filter.name} className="grid gap-2">
                                                    <Label htmlFor={filter.name}>{filter.label}</Label>
                                                    <Select
                                                        value={filterValues[filter.name] || ''}
                                                        onValueChange={(value) => handleFilterChange(filter.name, value)}
                                                    >
                                                        <SelectTrigger id={filter.name}>
                                                            <SelectValue placeholder={`Seleccionar ${filter.label.toLowerCase()}`} />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="all">{allLabel}</SelectItem>
                                                            {filter.options?.map((option) => (
                                                                <SelectItem key={option.value} value={option.value}>
                                                                    {option.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        )}

                        {/* Export Button */}
                        {exportUrl && !headerActions && (
                            <Button variant="outline" onClick={handleExport} className="gap-2">
                                <Download className="h-4 w-4" />
                                {exportLabel}
                            </Button>
                        )}
                    </div>

                    {/* Active Filters */}
                    {hasActiveFilters && (
                        <div className="flex flex-wrap items-center gap-2 border-b bg-muted/30 p-4">
                            <span className="text-sm font-medium text-muted-foreground">{activeFiltersTitle}</span>
                            {activeFilters.map((filter) => (
                                <Badge key={filter.name} variant="secondary" className="gap-1.5 pr-1.5 pl-2.5 font-normal">
                                    <span className="text-xs">
                                        {filter.label}: {filter.displayValue}
                                    </span>
                                    <button
                                        onClick={() => handleRemoveFilter(filter.name)}
                                        className="ml-1 rounded-sm hover:bg-muted-foreground/20"
                                        aria-label={`Eliminar filtro ${filter.label}`}
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                </Badge>
                            ))}
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={handleClearFilters}
                                className="h-7 text-xs text-muted-foreground hover:text-foreground"
                            >
                                {clearFiltersLabel}
                            </Button>
                        </div>
                    )}
                        </>
                    )}

                    <Table>
                        <TableHeader>
                            <TableRow>
                                {selectable && (
                                    <TableHead className="w-[50px]">
                                        <Checkbox
                                            checked={isAllSelected}
                                            onCheckedChange={toggleSelectAll}
                                            aria-label="Seleccionar todo"
                                        />
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
                                            onClick={() => column.sortable && handleSort(column.key)}
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
                            {data.data.length > 0 ? (
                                data.data.map((row, index) => {
                                    const rowData = row as Record<string, unknown>;
                                    const href = rowHref
                                        ? rowHref.replace(/\{(\w+)\}/g, (_, key) => String(rowData[key] ?? ''))
                                        : null;
                                    const rowKey = getRowKey(row, index);
                                    const isSelected = selectedIds.includes(rowKey);

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
                                                          // Don't navigate if clicking on buttons, links, or inputs
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
                                                        onCheckedChange={() => toggleSelectRow(rowKey)}
                                                        aria-label={`Seleccionar fila ${index + 1}`}
                                                    />
                                                </TableCell>
                                            )}
                                            {columns.map((column) => (
                                                <TableCell
                                                    key={column.key}
                                                    className={cn(
                                                        column.align === 'right' && 'text-right',
                                                        column.align === 'center' && 'text-center',
                                                        column.cellClassName,
                                                    )}
                                                >
                                                    {renderCellContent(row, column)}
                                                </TableCell>
                                            ))}
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
                         {/* Pagination */}
            {data.data.length > 0 && <Paginator className='border-b-0 border-l-0 border-r-0 rounded-none mt-6 pt-8 border-t' data={data} perPageOptions={resource.perPageOptions} />}

                </CardContent>
            </Card>
            {/* Confirmation Dialog */}
            <Dialog
                open={confirmationDialog.open}
                onOpenChange={(open) => {
                    if (!open && !isDeleting) {
                        setConfirmationDialog({ open: false, action: null, row: null });
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirmar acción</DialogTitle>
                        <DialogDescription>
                            {confirmationDialog.action?.confirmationMessage || '¿Estás seguro de que deseas realizar esta acción?'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setConfirmationDialog({ open: false, action: null, row: null })}
                            disabled={isDeleting}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant={confirmationDialog.action?.color === 'danger' ? 'destructive' : 'default'}
                            onClick={handleConfirmAction}
                            disabled={isDeleting}
                        >
                            {isDeleting ? 'Procesando...' : 'Confirmar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Bulk Confirmation Dialog */}
            <Dialog
                open={bulkConfirmationDialog.open}
                onOpenChange={(open) => {
                    if (!open && !isBulkProcessing) {
                        setBulkConfirmationDialog({ open: false, action: null });
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Confirmar acción masiva</DialogTitle>
                        <DialogDescription>
                            {bulkConfirmationDialog.action?.confirmationMessage ||
                                `¿Estás seguro de que deseas realizar esta acción en ${selectedIds.length} elemento${selectedIds.length !== 1 ? 's' : ''}?`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setBulkConfirmationDialog({ open: false, action: null })}
                            disabled={isBulkProcessing}
                        >
                            Cancelar
                        </Button>
                        <Button
                            variant={bulkConfirmationDialog.action?.color === 'danger' ? 'destructive' : 'default'}
                            onClick={handleConfirmBulkAction}
                            disabled={isBulkProcessing}
                        >
                            {isBulkProcessing ? 'Procesando...' : 'Confirmar'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

export default DataTable;
