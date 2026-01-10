import { useCallback, useState } from 'react';

import { type TableColumn } from '../types';

interface UseDataTableSortingProps {
    columns: TableColumn[];
    initialSortBy: string | null;
    initialSortDirection: 'asc' | 'desc';
    filterValues: Record<string, string>;
    navigateWithFilters: (newFilters: Record<string, string>, options?: { sortBy?: string; sortDirection?: 'asc' | 'desc' }) => void;
}

interface UseDataTableSortingReturn {
    currentSortBy: string | null;
    currentSortDirection: 'asc' | 'desc';
    handleSort: (columnKey: string) => void;
}

export function useDataTableSorting({
    columns,
    initialSortBy,
    initialSortDirection,
    filterValues,
    navigateWithFilters,
}: UseDataTableSortingProps): UseDataTableSortingReturn {
    const [currentSortBy, setCurrentSortBy] = useState<string | null>(initialSortBy);
    const [currentSortDirection, setCurrentSortDirection] = useState<'asc' | 'desc'>(initialSortDirection);

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

    return {
        currentSortBy,
        currentSortDirection,
        handleSort,
    };
}
