'use client';

import { Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronDown, DollarSign, Download, Edit, Eye, Filter, MoreHorizontal, Printer, Search, Trash2, X } from 'lucide-react';
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
import { DateRangePicker } from './date-range-picker';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from './dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from './dropdown-menu';
import { Input } from './input';
import { Label } from './label';
import { Paginator } from './paginator';
import { Popover, PopoverContent, PopoverTrigger } from './popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from './table';

// ============================================================================
// Types
// ============================================================================

export interface TableColumn {
    key: string;
    label: string;
    type: string;
    sortable: boolean;
    align?: 'left' | 'center' | 'right';
    className?: string;
}

export interface TableFilter {
    name: string;
    label: string;
    type: 'search' | 'select' | 'date';
    default?: unknown;
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
    hidden?: boolean;
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
}: DataTableProps<T>) {
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();
    const { data, columns, filters, appliedFilters, sortBy, sortDirection, rowHref } = resource;

    const [filterValues, setFilterValues] = useState<Record<string, string>>(appliedFilters);
    const [searchQuery, setSearchQuery] = useState(appliedFilters.search || '');
    const [currentSortBy, setCurrentSortBy] = useState<string | null>(sortBy);
    const [currentSortDirection, setCurrentSortDirection] = useState<'asc' | 'desc'>(sortDirection);
    const searchTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);

    // Initialize date range from applied filters
    const getInitialDateRange = useCallback((): DateRange | undefined => {
        const start = appliedFilters.start_date;
        const end = appliedFilters.end_date;
        if (start || end) {
            return {
                from: start ? parseISO(start) : undefined,
                to: end ? parseISO(end) : undefined,
            };
        }
        return undefined;
    }, [appliedFilters.start_date, appliedFilters.end_date]);

    const [dateRange, setDateRange] = useState<DateRange | undefined>(getInitialDateRange());

    // Confirmation dialog state
    const [confirmationDialog, setConfirmationDialog] = useState<{
        open: boolean;
        action: TableAction | null;
        row: T | null;
    }>({ open: false, action: null, row: null });
    const [isDeleting, setIsDeleting] = useState(false);

    // Get filters by type and visibility
    const searchFilter = filters.find((f) => f.type === 'search');
    const visibleFilters = filters.filter((f) => f.type === 'select' && !f.hidden);
    const hiddenFilters = filters.filter((f) => f.type === 'select' && f.hidden);
    const hasDateRangeFilter = filters.some((f) => f.name === 'start_date' || f.name === 'end_date');

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
            setDateRange(range);
            const newFilters = {
                ...filterValues,
                start_date: range?.from ? format(range.from, 'yyyy-MM-dd') : '',
                end_date: range?.to ? format(range.to, 'yyyy-MM-dd') : '',
            };
            setFilterValues(newFilters);
            navigateWithFilters(newFilters);
        },
        [filterValues, navigateWithFilters],
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
                if (filter.name === 'start_date' || filter.name === 'end_date') {
                    const startDate = filterValues.start_date;
                    const endDate = filterValues.end_date;
                    const startDefault = filters.find((f) => f.name === 'start_date')?.default;
                    const endDefault = filters.find((f) => f.name === 'end_date')?.default;

                    if (startDate && endDate && (startDate !== startDefault || endDate !== endDefault)) {
                        displayValue = `${format(parseISO(startDate), 'd MMM yyyy')} - ${format(parseISO(endDate), 'd MMM yyyy')}`;
                        active.push({
                            name: 'date_range',
                            label: 'Periodo',
                            displayValue,
                            isDateRange: true,
                        });
                        processedFilters.add('start_date');
                        processedFilters.add('end_date');
                    }
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

            if (filterName === 'date_range') {
                const startDateFilter = filters.find((f) => f.name === 'start_date');
                const endDateFilter = filters.find((f) => f.name === 'end_date');
                newFilters.start_date = (startDateFilter?.default as string) || '';
                newFilters.end_date = (endDateFilter?.default as string) || '';
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

                                if (action.isCustom || action.name === 'delete' || !action.href) {
                                    return (
                                        <Button
                                            key={action.name}
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleActionClick(action, row)}
                                            className={action.color === 'danger' ? 'text-red-600 hover:text-red-700 dark:text-red-400' : undefined}
                                        >
                                            {IconComponent && <IconComponent className="h-4 w-4" />}
                                        </Button>
                                    );
                                }

                                return (
                                    <Button key={action.name} variant="ghost" size="sm" asChild>
                                        <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                            {IconComponent && <IconComponent className="h-4 w-4" />}
                                        </Link>
                                    </Button>
                                );
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
                                                    >
                                                        {IconComponent && <IconComponent className="mr-2 h-4 w-4" />}
                                                        {action.label}
                                                    </DropdownMenuItem>
                                                );
                                            }

                                            // Regular actions with href use Link
                                            return (
                                                <DropdownMenuItem key={action.name} asChild>
                                                    <Link href={action.href} target={action.target} prefetch={action.prefetch}>
                                                        {IconComponent && <IconComponent className="mr-2 h-4 w-4" />}
                                                        {action.label}
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
    const hasFilters = filters.length > 0;
    const hasActiveFilters = activeFilters.length > 0;

    return (
        <div className={cn('space-y-4', className)}>
            {/* Top Filter Bar */}
            {hasFilters && (
                <div className="flex flex-wrap items-center gap-3">
                    {/* Date Range Picker */}
                    {hasDateRangeFilter && (
                        <DateRangePicker value={dateRange} onChange={handleDateRangeChange} placeholder="Seleccionar fechas" />
                    )}

                    {/* Visible Filters */}
                    {visibleFilters.map((filter) => (
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

                    {/* Hidden Filters Popover */}
                    {hiddenFilters.length > 0 && (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" className="gap-2">
                                    <Filter className="h-4 w-4" />
                                    {moreFiltersLabel}
                                    {Object.keys(filterValues).filter(
                                        (key) => hiddenFilters.some((f) => f.name === key) && filterValues[key] && filterValues[key] !== 'all',
                                    ).length > 0 && (
                                        <Badge variant="secondary" className="ml-1 h-5 min-w-5 rounded-full px-1.5">
                                            {
                                                Object.keys(filterValues).filter(
                                                    (key) =>
                                                        hiddenFilters.some((f) => f.name === key) &&
                                                        filterValues[key] &&
                                                        filterValues[key] !== 'all',
                                                ).length
                                            }
                                        </Badge>
                                    )}
                                    <ChevronDown className="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-80" align="start">
                                <div className="grid gap-4">
                                    <div className="space-y-2">
                                        <h4 className="font-medium leading-none">Filtros</h4>
                                        <p className="text-sm text-muted-foreground">Configura los filtros adicionales.</p>
                                    </div>
                                    <div className="grid gap-4">
                                        {hiddenFilters.map((filter) => (
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

                    {/* Header Actions */}
                    <div className="ml-auto flex items-center gap-2">
                        {headerActions}

                        {/* Export Button */}
                        {exportUrl && (
                            <Button variant="outline" onClick={handleExport} className="gap-2">
                                <Download className="h-4 w-4" />
                                {exportLabel}
                            </Button>
                        )}
                    </div>
                </div>
            )}

            {/* Data Table Card */}
            <Card>
                <CardContent className="p-0">
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

                    {/* Table Header with Search */}
                    {searchFilter && (
                        <div className="flex items-center gap-4 border-b p-4">
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
                        </div>
                    )}

                    <Table>
                        <TableHeader>
                            <TableRow>
                                {columns.map((column) => (
                                    <TableHead
                                        key={column.key}
                                        className={cn(
                                            column.align === 'right' && 'text-right',
                                            column.align === 'center' && 'text-center',
                                            column.sortable && 'cursor-pointer select-none hover:bg-muted/50',
                                            column.className,
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
                                                        <ArrowUpDown className="h-4 w-4 text-muted-foreground/50" />
                                                    )}
                                                </span>
                                            )}
                                        </div>
                                    </TableHead>
                                ))}
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

                                    return (
                                        <TableRow
                                            key={getRowKey(row, index)}
                                            className={href ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50' : undefined}
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
                                            {columns.map((column) => (
                                                <TableCell
                                                    key={column.key}
                                                    className={cn(
                                                        column.align === 'right' && 'text-right',
                                                        column.align === 'center' && 'text-center',
                                                        column.className,
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
                                    <TableCell colSpan={columns.length + (rowActions ? 1 : 0)} className="h-24 text-center">
                                        {emptyState || emptyMessage}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* Pagination */}
            {data.data.length > 0 && <Paginator data={data} />}

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
        </div>
    );
}

export default DataTable;
