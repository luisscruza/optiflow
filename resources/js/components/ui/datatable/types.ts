import { type PaginatedResponse } from '@/types';

// ============================================================================
// Column Types
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
    copiable?: boolean;
}

export type RenderCellFn<T> = (columnKey: string, value: unknown, row: T) => React.ReactNode | undefined;

// ============================================================================
// Filter Types
// ============================================================================

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

export interface ActiveFilter {
    name: string;
    label: string;
    displayValue: string;
    isDateRange?: boolean;
}

// ============================================================================
// Action Types
// ============================================================================

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
    method?: 'get' | 'post';
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

// ============================================================================
// Resource Types
// ============================================================================

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

// ============================================================================
// Component Props Types
// ============================================================================

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
    /** Custom cell renderer function */
    renderCell?: RenderCellFn<T>;
}

// ============================================================================
// Internal Types
// ============================================================================

export interface StatusConfig {
    value: string;
    label: string;
    variant: string;
    className?: string;
}

export interface ConfirmationDialogState<T> {
    open: boolean;
    action: TableAction | null;
    row: T | null;
}

export interface BulkConfirmationDialogState {
    open: boolean;
    action: BulkAction | null;
}
