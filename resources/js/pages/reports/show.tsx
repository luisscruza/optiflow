import { Head, Link, router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronDown, Download, Filter, Search, X } from 'lucide-react';
import { useState } from 'react';
import { DateRange } from 'react-day-picker';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Paginator } from '@/components/ui/paginator';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Report {
    id: number;
    type: string;
    name: string;
    description: string;
    group: string;
    groupLabel: string;
}

interface ReportFilter {
    name: string;
    label: string;
    type: string;
    default?: unknown;
    options?: Array<{ value: string; label: string }>;
    placeholder?: string;
    hidden?: boolean;
}

interface ReportColumn {
    key: string;
    label: string;
    type: string;
    sortable: boolean;
    align?: string;
    href?: string;
}

interface SummaryItem {
    key: string;
    label: string;
    value: number;
    type: string;
}

interface StatusConfig {
    value: string;
    label: string;
    variant: string;
    className: string;
}

interface Props {
    report: Report;
    filters: ReportFilter[];
    columns: ReportColumn[];
    summary: SummaryItem[];
    data: PaginatedData<Record<string, unknown>>;
    appliedFilters: Record<string, string>;
    sortBy: string | null;
    sortDirection: 'asc' | 'desc';
}

const breadcrumbs = (reportName: string, groupLabel: string, groupValue: string): BreadcrumbItem[] => [
    {
        title: 'Reportes',
        href: '/reports',
    },
    {
        title: groupLabel,
        href: `/reports/group/${groupValue}`,
    },
    {
        title: reportName,
        href: '#',
    },
];

export default function ReportShow({ report, filters, columns, summary, data, appliedFilters, sortBy, sortDirection }: Props) {
    const { format: formatCurrency } = useCurrency();
    const [filterValues, setFilterValues] = useState<Record<string, string>>(appliedFilters);
    const [searchQuery, setSearchQuery] = useState(appliedFilters.search || '');
    const [currentSortBy, setCurrentSortBy] = useState<string | null>(sortBy);
    const [currentSortDirection, setCurrentSortDirection] = useState<'asc' | 'desc'>(sortDirection);

    const handleSort = (columnKey: string) => {
        const column = columns.find((c) => c.key === columnKey);
        if (!column?.sortable) return;

        let newDirection: 'asc' | 'desc' = 'asc';
        if (currentSortBy === columnKey) {
            newDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        }

        setCurrentSortBy(columnKey);
        setCurrentSortDirection(newDirection);

        router.get(
            `/reports/${report.type}`,
            { ...filterValues, sort_by: columnKey, sort_direction: newDirection },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleFilterChange = (name: string, value: string) => {
        const newFilters = { ...filterValues, [name]: value };
        setFilterValues(newFilters);
        router.get(`/reports/${report.type}`, newFilters, { preserveState: true, preserveScroll: true });
    };

    const handleSearch = (value: string) => {
        setSearchQuery(value);
        const newFilters = { ...filterValues, search: value };
        setFilterValues(newFilters);

        // Debounce search
        const timeoutId = setTimeout(() => {
            router.get(`/reports/${report.type}`, newFilters, { preserveState: true, preserveScroll: true });
        }, 500);

        return () => clearTimeout(timeoutId);
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        Object.entries(filterValues).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });
        window.location.href = `/reports/${report.type}/export?${params.toString()}`;
    };

    const getActiveFilters = () => {
        const active: Array<{ name: string; label: string; displayValue: string; isDateRange?: boolean }> = [];
        const processedFilters = new Set<string>();

        filters.forEach((filter) => {
            // Skip if already processed (for date ranges)
            if (processedFilters.has(filter.name)) {
                return;
            }

            const value = filterValues[filter.name];
            const defaultValue = filter.default;

            // Skip if value is the same as default or empty
            if (!value || value === defaultValue || value === 'all' || value === '') {
                return;
            }

            let displayValue = '';

            if (filter.type === 'search') {
                displayValue = `"${value}"`;
            } else if (filter.type === 'date') {
                // Check if this is part of a date range (start_date/end_date)
                if (filter.name === 'start_date' || filter.name === 'end_date') {
                    const startDate = filterValues.start_date;
                    const endDate = filterValues.end_date;
                    const startDefault = filters.find((f) => f.name === 'start_date')?.default;
                    const endDefault = filters.find((f) => f.name === 'end_date')?.default;

                    // Only show if both dates differ from defaults
                    if (startDate && endDate && (startDate !== startDefault || endDate !== endDefault)) {
                        displayValue = `${format(parseISO(startDate as string), 'd MMM yyyy')} - ${format(parseISO(endDate as string), 'd MMM yyyy')}`;
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
                displayValue = format(parseISO(value as string), 'd MMM yyyy');
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
    };

    const handleRemoveFilter = (filterName: string) => {
        let newFilters = { ...filterValues };

        // Handle date range removal
        if (filterName === 'date_range') {
            const startDateFilter = filters.find((f) => f.name === 'start_date');
            const endDateFilter = filters.find((f) => f.name === 'end_date');
            newFilters.start_date = (startDateFilter?.default as string) || '';
            newFilters.end_date = (endDateFilter?.default as string) || '';
        } else {
            const filter = filters.find((f) => f.name === filterName);
            const defaultValue = filter?.default || 'all';
            newFilters[filterName] = defaultValue as string;

            if (filterName === 'search') {
                setSearchQuery('');
            }
        }

        setFilterValues(newFilters);
        router.get(`/reports/${report.type}`, newFilters, { preserveState: true, preserveScroll: true });
    };

    const handleClearFilters = () => {
        const defaultFilters: Record<string, string> = {};
        filters.forEach((filter) => {
            defaultFilters[filter.name] = (filter.default as string) || '';
        });
        setFilterValues(defaultFilters);
        setSearchQuery('');
        router.get(`/reports/${report.type}`, defaultFilters, { preserveState: true, preserveScroll: true });
    };

    const renderCellContent = (row: Record<string, unknown>, column: ReportColumn) => {
        const value = row[column.key];
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
                    <Badge variant={statusConfig.variant as 'default' | 'destructive' | 'outline' | 'secondary'} className={statusConfig.className}>
                        {statusConfig.label}
                    </Badge>
                );
                break;
            }
            case 'date':
                content = String(value);
                break;
            case 'link': {
                const href = column.href?.replace(/\{(\w+)\}/g, (_, key) => String(row[key] || '')) || '#';
                content = (
                    <Link href={href} className="font-medium text-primary hover:underline">
                        {String(value)}
                    </Link>
                );
                break;
            }
            case 'prescription': {
                const prescriptionData = value as {
                    od: { esfera?: string; cilindro?: string; eje?: string; add?: string; av_lejos?: string; av_cerca?: string };
                    oi: { esfera?: string; cilindro?: string; eje?: string; add?: string; av_lejos?: string; av_cerca?: string };
                };
                content = (
                    <div className="space-y-1 text-xs">
                        <div>
                            <strong>OD:</strong> E:{prescriptionData.od.esfera || '-'} C:{prescriptionData.od.cilindro || '-'} Eje:
                            {prescriptionData.od.eje || '-'} Add:{prescriptionData.od.add || '-'}
                        </div>
                        <div>
                            <strong>OI:</strong> E:{prescriptionData.oi.esfera || '-'} C:{prescriptionData.oi.cilindro || '-'} Eje:
                            {prescriptionData.oi.eje || '-'} Add:{prescriptionData.oi.add || '-'}
                        </div>
                    </div>
                );
                break;
            }
            case 'invoices': {
                const invoices = value as Array<{
                    id: number;
                    document_number: string;
                    total_amount: number;
                    issue_date: string;
                    status: string;
                }>;
                if (!invoices || invoices.length === 0) {
                    content = <span className="text-xs text-muted-foreground">Sin facturas</span>;
                } else {
                    content = (
                        <div className="space-y-1">
                            {invoices.map((invoice) => (
                                <div key={invoice.id} className="flex items-center gap-2 text-xs">
                                    <Link href={`/invoices/${invoice.id}`} className="font-medium text-primary hover:underline">
                                        {invoice.document_number}
                                    </Link>
                                    <Badge
                                        variant={
                                            invoice.status === 'paid' ? 'default' : invoice.status === 'partially_paid' ? 'secondary' : 'outline'
                                        }
                                        className="text-[10px]"
                                    >
                                        {invoice.status === 'paid' ? 'Pagada' : invoice.status === 'partially_paid' ? 'Parcial' : 'Pendiente'}
                                    </Badge>
                                    <span className="text-muted-foreground">{formatCurrency(invoice.total_amount)}</span>
                                </div>
                            ))}
                        </div>
                    );
                }
                break;
            }
            case 'invoice_list': {
                const invoiceList = value as Array<{
                    id: number;
                    document_number: string;
                    total_amount: number;
                    issue_date: string;
                    workspace_name?: string;
                }>;
                if (!invoiceList || invoiceList.length === 0) {
                    content = <span className="text-sm text-muted-foreground">0</span>;
                } else {
                    content = (
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="link" className="h-auto p-0 font-semibold">
                                    {invoiceList.length}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-96" align="center">
                                <div className="space-y-2">
                                    <h4 className="text-sm font-semibold">Facturas ({invoiceList.length})</h4>
                                    <div className="max-h-[300px] space-y-2 overflow-y-auto">
                                        {invoiceList.map((invoice) => (
                                            <Link
                                                key={invoice.id}
                                                href={`/invoices/${invoice.id}`}
                                                className="flex items-center justify-between rounded-md border bg-card p-2 text-xs transition-colors hover:bg-accent"
                                            >
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-medium text-primary">{invoice.document_number}</span>
                                                    <div className="flex items-center gap-2 text-muted-foreground">
                                                        <span>{invoice.issue_date}</span>
                                                        {invoice.workspace_name && (
                                                            <>
                                                                <span>·</span>
                                                                <span>{invoice.workspace_name}</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                                <span className="font-medium">{formatCurrency(invoice.total_amount)}</span>
                                            </Link>
                                        ))}
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    );
                }
                break;
            }
            default:
                content = String(value);
        }

        // If column has href, wrap content in Link
        if (column.href && column.type !== 'link') {
            const href = column.href.replace(/\{(\w+)\}/g, (_, key) => String(row[key] || ''));
            return (
                <Link href={href} className="font-medium text-primary hover:underline">
                    {content}
                </Link>
            );
        }

        return content;
    };

    const renderSummaryValue = (item: SummaryItem) => {
        if (item.type === 'currency') {
            return formatCurrency(item.value);
        }
        return item.value.toLocaleString();
    };

    // Get filters by type and visibility
    const searchFilter = filters.find((f) => f.type === 'search');
    const visibleFilters = filters.filter((f) => f.type === 'select' && !f.hidden);
    const hiddenFilters = filters.filter((f) => f.type === 'select' && f.hidden);
    const hasDateRangeFilter = filters.some((f) => f.name === 'start_date' || f.name === 'end_date');

    // Initialize date range from applied filters
    const getInitialDateRange = (): DateRange | undefined => {
        const start = appliedFilters.start_date;
        const end = appliedFilters.end_date;
        if (start || end) {
            return {
                from: start ? parseISO(start) : undefined,
                to: end ? parseISO(end) : undefined,
            };
        }
        return undefined;
    };

    const [dateRange, setDateRange] = useState<DateRange | undefined>(getInitialDateRange());

    const handleDateRangeChange = (range: DateRange | undefined) => {
        setDateRange(range);
        const newFilters = {
            ...filterValues,
            start_date: range?.from ? format(range.from, 'yyyy-MM-dd') : '',
            end_date: range?.to ? format(range.to, 'yyyy-MM-dd') : '',
        };
        setFilterValues(newFilters);
        router.get(`/reports/${report.type}`, newFilters, { preserveState: true, preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(report.name, report.groupLabel, report.group)}>
            <Head title={report.name} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <h2 className="mb-1 text-2xl leading-tight font-bold text-gray-900 dark:text-gray-100">{report.name}</h2>
                {report.description && <p className="mb-6 text-gray-600 dark:text-gray-400">{report.description}</p>}
                <div className="space-y-6">
                    {/* Top Filter Bar */}
                    <div className="flex flex-wrap items-center gap-3">
                        {/* Date Range Picker */}
                        {hasDateRangeFilter && (
                            <DateRangePicker value={dateRange} onChange={handleDateRangeChange} placeholder="Seleccionar fechas" />
                        )}

                        {/* Visible Filters (not hidden) */}
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
                                    <SelectItem value="all">Todos</SelectItem>
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
                                        Más filtros
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
                                            <h4 className="leading-none font-medium">Filtros</h4>
                                            <p className="text-sm text-muted-foreground">Configura los filtros adicionales del reporte.</p>
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
                                                            <SelectItem value="all">Todos</SelectItem>
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
                        <Button variant="outline" onClick={handleExport} className="ml-auto gap-2">
                            <Download className="h-4 w-4" />
                            Exportar
                        </Button>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {summary.map((item) => (
                            <Card key={item.key} className="border-border/40 bg-card/50">
                                <CardContent className="p-4">
                                    <p className="text-sm text-muted-foreground">{item.label}</p>
                                    <p className="mt-1 text-2xl font-bold tracking-tight">{renderSummaryValue(item)}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {/* Data Table */}
                    <Card>
                        <CardContent className="p-0">
                            {/* Active Filters */}
                            {getActiveFilters().length > 0 && (
                                <div className="flex flex-wrap items-center gap-2 border-b bg-muted/30 p-4">
                                    <span className="text-sm font-medium text-muted-foreground">Filtros activos:</span>
                                    {getActiveFilters().map((filter) => (
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
                                        Limpiar filtros
                                    </Button>
                                </div>
                            )}

                            {/* Table Header with Search */}
                            <div className="flex items-center gap-4 border-b p-4">
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
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {columns.map((column) => (
                                            <TableHead
                                                key={column.key}
                                                className={`${column.align === 'right' ? 'text-right' : ''} ${column.sortable ? 'cursor-pointer select-none hover:bg-muted/50' : ''}`}
                                                onClick={() => column.sortable && handleSort(column.key)}
                                            >
                                                <div className={`flex items-center gap-1 ${column.align === 'right' ? 'justify-end' : ''}`}>
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
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {data.data.length > 0 ? (
                                        data.data.map((row, index) => (
                                            <TableRow key={index}>
                                                {columns.map((column) => (
                                                    <TableCell key={column.key} className={column.align === 'right' ? 'text-right' : ''}>
                                                        {renderCellContent(row, column)}
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={columns.length} className="h-24 text-center">
                                                No hay datos disponibles para los filtros seleccionados.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Pagination */}
                    {data.data.length > 0 && <Paginator data={data} />}
                </div>
            </div>
        </AppLayout>
    );
}
