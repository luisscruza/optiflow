import { Filter, Search, X } from 'lucide-react';
import * as React from 'react';
import { DateRange } from 'react-day-picker';

import { cn } from '@/lib/utils';

import { Badge } from '../../badge';
import { Button } from '../../button';
import { DateRangePicker } from '../../date-range-picker';
import { Input } from '../../input';
import { Label } from '../../label';
import { Popover, PopoverContent, PopoverTrigger } from '../../popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../../select';
import { type ActiveFilter, type TableFilter } from '../types';

interface DataTableFiltersProps {
    searchFilter?: TableFilter;
    searchQuery: string;
    onSearch: (value: string) => void;
    inlineSelectFilters: TableFilter[];
    popoverSelectFilters: TableFilter[];
    hiddenFilters: TableFilter[];
    inlineDateRangeFilters: TableFilter[];
    popoverDateRangeFilters: TableFilter[];
    dateRange: DateRange | undefined;
    filterValues: Record<string, string>;
    onFilterChange: (name: string, value: string) => void;
    onDateRangeChange: (range: DateRange | undefined) => void;
    hasPopoverFilters: boolean;
    activeFilters: ActiveFilter[];
    hasActiveFilters: boolean;
    onRemoveFilter: (filterName: string) => void;
    onClearFilters: () => void;
    allLabel?: string;
    activeFiltersTitle?: string;
    clearFiltersLabel?: string;
}

export const DataTableFilters: React.FC<DataTableFiltersProps> = ({
    searchFilter,
    searchQuery,
    onSearch,
    inlineSelectFilters,
    popoverSelectFilters,
    hiddenFilters,
    inlineDateRangeFilters,
    popoverDateRangeFilters,
    dateRange,
    filterValues,
    onFilterChange,
    onDateRangeChange,
    hasPopoverFilters,
    activeFilters,
    hasActiveFilters,
    onRemoveFilter,
    onClearFilters,
    allLabel = 'Todos',
    activeFiltersTitle = 'Filtros activos:',
    clearFiltersLabel = 'Limpiar filtros',
}) => {
    const allPopoverSelectFilters = [...popoverSelectFilters, ...hiddenFilters];

    return (
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
                            onChange={(e) => onSearch(e.target.value)}
                        />
                    </div>
                )}

                {/* Inline Date Range Filters */}
                {inlineDateRangeFilters.map((filter) => (
                    <DateRangePicker
                        key={filter.name}
                        value={dateRange}
                        onChange={onDateRangeChange}
                        placeholder={filter.placeholder || filter.label}
                    />
                ))}

                {/* Inline Select Filters */}
                {inlineSelectFilters.map((filter) => (
                    <Select
                        key={filter.name}
                        value={filterValues[filter.name] || ''}
                        onValueChange={(value) => onFilterChange(filter.name, value)}
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
                                    <h4 className="font-medium leading-none">Filtrar por</h4>
                                </div>
                                <div className="grid gap-4">
                                    {/* Popover Date Range Filters */}
                                    {popoverDateRangeFilters.map((filter) => (
                                        <div key={filter.name} className="grid gap-2">
                                            <Label>{filter.label}</Label>
                                            <DateRangePicker
                                                value={dateRange}
                                                onChange={onDateRangeChange}
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
                                                onValueChange={(value) => onFilterChange(filter.name, value)}
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
                                onClick={() => onRemoveFilter(filter.name)}
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
                        onClick={onClearFilters}
                        className="h-7 text-xs text-muted-foreground hover:text-foreground"
                    >
                        {clearFiltersLabel}
                    </Button>
                </div>
            )}
        </>
    );
};
