'use client';

import { Download } from 'lucide-react';
import * as React from 'react';
import { useCallback } from 'react';

import { cn } from '@/lib/utils';
import { useCurrency } from '@/utils/currency';

import { Button } from '../button';
import { Card, CardContent } from '../card';
import { Paginator } from '../paginator';

import {
    BulkConfirmationDialog,
    ConfirmationDialog,
    DataTableBulkActionsBar,
    DataTableContent,
    DataTableFilters,
} from './components';
import { useDataTableActions, useDataTableFilters, useDataTableSelection, useDataTableSorting } from './hooks';
import { type DataTableProps } from './types';

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
    searchDebounce = 500,
    onAction,
    handlers = {},
    bulkHandlers = {},
    renderCell,
}: DataTableProps<T>) {
    const { format: formatCurrency } = useCurrency();
    const { data, columns, filters, appliedFilters, sortBy, sortDirection, rowHref, selectable, bulkActions = [] } = resource;

    // Selection hook
    const {
        selectedIds,
        isAllSelected,
        hasSelection,
        toggleSelectAll,
        toggleSelectRow,
        clearSelection,
        isRowSelected,
    } = useDataTableSelection({ data: data.data, getRowKey });

    // We need to initialize sorting first to get currentSortBy/Direction for filters
    const [currentSortByState, setCurrentSortByState] = React.useState<string | null>(sortBy);
    const [currentSortDirectionState, setCurrentSortDirectionState] = React.useState<'asc' | 'desc'>(sortDirection);

    // Filters hook
    const filtersState = useDataTableFilters({
        filters,
        appliedFilters,
        baseUrl,
        currentSortBy: currentSortByState,
        currentSortDirection: currentSortDirectionState,
        searchDebounce,
    });

    // Sorting hook (uses navigateWithFilters from filters)
    const { currentSortBy, currentSortDirection, handleSort } = useDataTableSorting({
        columns,
        initialSortBy: sortBy,
        initialSortDirection: sortDirection,
        filterValues: filtersState.filterValues,
        navigateWithFilters: filtersState.navigateWithFilters,
    });

    // Sync sort state for filters navigation
    React.useEffect(() => {
        setCurrentSortByState(currentSortBy);
        setCurrentSortDirectionState(currentSortDirection);
    }, [currentSortBy, currentSortDirection]);

    // Actions hook
    const {
        confirmationDialog,
        bulkConfirmationDialog,
        isDeleting,
        isBulkProcessing,
        handleActionClick,
        handleConfirmAction,
        handleBulkAction,
        handleConfirmBulkAction,
        closeConfirmationDialog,
        closeBulkConfirmationDialog,
    } = useDataTableActions({
        onAction,
        handlers,
        bulkHandlers,
        selectedIds,
        clearSelection,
    });

    const handleExport = useCallback(() => {
        if (!exportUrl) return;
        const params = new URLSearchParams();
        Object.entries(filtersState.filterValues).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });
        window.location.href = `${exportUrl}?${params.toString()}`;
    }, [exportUrl, filtersState.filterValues]);

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
                    {/* Bulk Actions Bar or Filters */}
                    {hasSelection ? (
                        <DataTableBulkActionsBar
                            selectedCount={selectedIds.length}
                            bulkActions={bulkActions}
                            onClearSelection={clearSelection}
                            onBulkAction={handleBulkAction}
                            isBulkProcessing={isBulkProcessing}
                        />
                    ) : (
                        <DataTableFilters
                            searchFilter={filtersState.searchFilter}
                            searchQuery={filtersState.searchQuery}
                            onSearch={filtersState.handleSearch}
                            inlineSelectFilters={filtersState.inlineSelectFilters}
                            popoverSelectFilters={filtersState.popoverSelectFilters}
                            hiddenFilters={filtersState.hiddenFilters}
                            inlineDateRangeFilters={filtersState.inlineDateRangeFilters}
                            popoverDateRangeFilters={filtersState.popoverDateRangeFilters}
                            dateRange={filtersState.dateRange}
                            filterValues={filtersState.filterValues}
                            onFilterChange={filtersState.handleFilterChange}
                            onDateRangeChange={filtersState.handleDateRangeChange}
                            hasPopoverFilters={filtersState.hasPopoverFilters}
                            activeFilters={filtersState.activeFilters}
                            hasActiveFilters={filtersState.hasActiveFilters}
                            onRemoveFilter={filtersState.handleRemoveFilter}
                            onClearFilters={filtersState.handleClearFilters}
                            allLabel={allLabel}
                            activeFiltersTitle={activeFiltersTitle}
                            clearFiltersLabel={clearFiltersLabel}
                        />
                    )}

                    {/* Table Content */}
                    <DataTableContent
                        data={data.data}
                        columns={columns}
                        selectable={selectable}
                        rowHref={rowHref}
                        emptyMessage={emptyMessage}
                        emptyState={emptyState}
                        currentSortBy={currentSortBy}
                        currentSortDirection={currentSortDirection}
                        onSort={handleSort}
                        isAllSelected={isAllSelected}
                        onToggleSelectAll={toggleSelectAll}
                        getRowKey={getRowKey}
                        isRowSelected={isRowSelected}
                        onToggleSelectRow={toggleSelectRow}
                        rowActions={rowActions}
                        formatCurrency={formatCurrency}
                        onActionClick={handleActionClick}
                        renderCell={renderCell}
                    />

                    {/* Pagination */}
                    {data.data.length > 0 && (
                        <Paginator
                            className="mt-6 rounded-none border-t border-b-0 border-l-0 border-r-0 pt-8"
                            data={data}
                            perPageOptions={resource.perPageOptions}
                        />
                    )}
                </CardContent>
            </Card>

            {/* Confirmation Dialog */}
            <ConfirmationDialog
                state={confirmationDialog}
                isProcessing={isDeleting}
                onClose={closeConfirmationDialog}
                onConfirm={handleConfirmAction}
            />

            {/* Bulk Confirmation Dialog */}
            <BulkConfirmationDialog
                state={bulkConfirmationDialog}
                selectedCount={selectedIds.length}
                isProcessing={isBulkProcessing}
                onClose={closeBulkConfirmationDialog}
                onConfirm={handleConfirmBulkAction}
            />
        </div>
    );
}

export default DataTable;
