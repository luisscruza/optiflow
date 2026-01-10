import { useCallback, useState } from 'react';

interface UseDataTableSelectionProps<T> {
    data: T[];
    getRowKey: (row: T, index: number) => string | number;
}

interface UseDataTableSelectionReturn {
    selectedIds: (string | number)[];
    isAllSelected: boolean;
    isSomeSelected: boolean;
    hasSelection: boolean;
    toggleSelectAll: () => void;
    toggleSelectRow: (rowKey: string | number) => void;
    clearSelection: () => void;
    isRowSelected: (rowKey: string | number) => boolean;
}

export function useDataTableSelection<T>({
    data,
    getRowKey,
}: UseDataTableSelectionProps<T>): UseDataTableSelectionReturn {
    const [selectedIds, setSelectedIds] = useState<(string | number)[]>([]);

    const isAllSelected = data.length > 0 && selectedIds.length === data.length;
    const isSomeSelected = selectedIds.length > 0 && selectedIds.length < data.length;
    const hasSelection = selectedIds.length > 0;

    const toggleSelectAll = useCallback(() => {
        if (isAllSelected) {
            setSelectedIds([]);
        } else {
            setSelectedIds(data.map((row: T, index: number) => getRowKey(row, index)));
        }
    }, [data, getRowKey, isAllSelected]);

    const toggleSelectRow = useCallback((rowKey: string | number) => {
        setSelectedIds((prev) =>
            prev.includes(rowKey) ? prev.filter((id) => id !== rowKey) : [...prev, rowKey],
        );
    }, []);

    const clearSelection = useCallback(() => {
        setSelectedIds([]);
    }, []);

    const isRowSelected = useCallback(
        (rowKey: string | number) => selectedIds.includes(rowKey),
        [selectedIds],
    );

    return {
        selectedIds,
        isAllSelected,
        isSomeSelected,
        hasSelection,
        toggleSelectAll,
        toggleSelectRow,
        clearSelection,
        isRowSelected,
    };
}
