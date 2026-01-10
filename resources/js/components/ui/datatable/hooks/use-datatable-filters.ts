import { router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import * as React from 'react';
import { useCallback, useState } from 'react';
import { DateRange } from 'react-day-picker';

import { type ActiveFilter, type TableFilter } from '../types';

interface UseDataTableFiltersProps {
    filters: TableFilter[];
    appliedFilters: Record<string, string>;
    baseUrl: string;
    currentSortBy: string | null;
    currentSortDirection: 'asc' | 'desc';
    searchDebounce: number;
}

interface UseDataTableFiltersReturn {
    filterValues: Record<string, string>;
    searchQuery: string;
    dateRange: DateRange | undefined;
    activeFilters: ActiveFilter[];
    hasActiveFilters: boolean;
    searchFilter: TableFilter | undefined;
    inlineSelectFilters: TableFilter[];
    popoverSelectFilters: TableFilter[];
    hiddenFilters: TableFilter[];
    dateRangeFilters: TableFilter[];
    inlineDateRangeFilters: TableFilter[];
    popoverDateRangeFilters: TableFilter[];
    hasPopoverFilters: boolean;
    hasInlineFilters: boolean;
    handleFilterChange: (name: string, value: string) => void;
    handleSearch: (value: string) => void;
    handleDateRangeChange: (range: DateRange | undefined) => void;
    handleRemoveFilter: (filterName: string) => void;
    handleClearFilters: () => void;
    navigateWithFilters: (newFilters: Record<string, string>, options?: { sortBy?: string; sortDirection?: 'asc' | 'desc' }) => void;
}

export function useDataTableFilters({
    filters,
    appliedFilters,
    baseUrl,
    currentSortBy,
    currentSortDirection,
    searchDebounce,
}: UseDataTableFiltersProps): UseDataTableFiltersReturn {
    const [filterValues, setFilterValues] = useState<Record<string, string>>(appliedFilters);
    const [searchQuery, setSearchQuery] = useState(appliedFilters.search || '');
    const searchTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);

    // Get filters by type and visibility
    const searchFilter = filters.find((f) => f.type === 'search');
    const inlineSelectFilters = filters.filter((f) => f.type === 'select' && f.inline && !f.hidden);
    const popoverSelectFilters = filters.filter((f) => f.type === 'select' && !f.inline && !f.hidden);
    const hiddenFilters = filters.filter((f) => f.type === 'select' && f.hidden);
    const dateRangeFilters = filters.filter((f) => f.type === 'date' && f.name.endsWith('_start'));
    const inlineDateRangeFilters = dateRangeFilters.filter((f) => f.inline);
    const popoverDateRangeFilters = dateRangeFilters.filter((f) => !f.inline);

    const allPopoverSelectFilters = [...popoverSelectFilters, ...hiddenFilters];
    const hasPopoverFilters = allPopoverSelectFilters.length > 0 || popoverDateRangeFilters.length > 0;
    const hasInlineFilters = inlineSelectFilters.length > 0 || inlineDateRangeFilters.length > 0;

    // Initialize date range from applied filters
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

    const getActiveFilters = useCallback((): ActiveFilter[] => {
        const active: ActiveFilter[] = [];
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

    const activeFilters = getActiveFilters();
    const hasActiveFilters = activeFilters.length > 0;

    return {
        filterValues,
        searchQuery,
        dateRange,
        activeFilters,
        hasActiveFilters,
        searchFilter,
        inlineSelectFilters,
        popoverSelectFilters,
        hiddenFilters,
        dateRangeFilters,
        inlineDateRangeFilters,
        popoverDateRangeFilters,
        hasPopoverFilters,
        hasInlineFilters,
        handleFilterChange,
        handleSearch,
        handleDateRangeChange,
        handleRemoveFilter,
        handleClearFilters,
        navigateWithFilters,
    };
}
