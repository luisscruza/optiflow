import { Head, Link, router } from '@inertiajs/react';
import { Calendar, ChevronDown, Download, Filter, Search } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
}

interface ReportColumn {
    key: string;
    label: string;
    type: string;
    sortable: boolean;
    align?: string;
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

export default function ReportShow({ report, filters, columns, summary, data, appliedFilters }: Props) {
    const { format: formatCurrency } = useCurrency();
    const [filterValues, setFilterValues] = useState<Record<string, string>>(appliedFilters);
    const [searchQuery, setSearchQuery] = useState(appliedFilters.search || '');

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
        // TODO: Implement export functionality
        console.log('Export report');
    };

    const renderCellContent = (row: Record<string, unknown>, column: ReportColumn) => {
        const value = row[column.key];

        switch (column.type) {
            case 'currency':
                return formatCurrency(Number(value));
            case 'badge': {
                const statusConfig = value as StatusConfig;
                return (
                    <Badge variant={statusConfig.variant as 'default' | 'destructive' | 'outline' | 'secondary'} className={statusConfig.className}>
                        {statusConfig.label}
                    </Badge>
                );
            }
            case 'date':
                return String(value);
            case 'link':
                return (
                    <Link href={`/invoices/${row.id}`} className="font-medium text-primary hover:underline">
                        {String(value)}
                    </Link>
                );
            default:
                return String(value);
        }
    };

    const renderSummaryValue = (item: SummaryItem) => {
        if (item.type === 'currency') {
            return formatCurrency(item.value);
        }
        return item.value.toLocaleString();
    };

    // Get filters by type
    const searchFilter = filters.find((f) => f.type === 'search');
    const dateFilters = filters.filter((f) => f.type === 'date');
    const selectFilters = filters.filter((f) => f.type === 'select');

    const formatDateRange = () => {
        const start = filterValues.start_date;
        const end = filterValues.end_date;
        if (start && end) {
            return `${new Date(start).toLocaleDateString('es-ES')} - ${new Date(end).toLocaleDateString('es-ES')}`;
        }
        if (start) return `Desde ${new Date(start).toLocaleDateString('es-ES')}`;
        if (end) return `Hasta ${new Date(end).toLocaleDateString('es-ES')}`;
        return 'Seleccionar fechas';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(report.name, report.groupLabel, report.group)}>
            <Head title={report.name} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    {/* Top Filter Bar */}
                    <div className="flex flex-wrap items-center gap-3">
                        {/* Date Range Picker */}
                        <Popover>
                            <PopoverTrigger asChild>
                                <Button variant="outline" className="gap-2">
                                    <Calendar className="h-4 w-4" />
                                    {formatDateRange()}
                                    <ChevronDown className="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-auto p-4" align="start">
                                <div className="grid gap-4">
                                    {dateFilters.map((filter) => (
                                        <div key={filter.name} className="grid gap-2">
                                            <label className="text-sm font-medium">{filter.label}</label>
                                            <Input
                                                type="date"
                                                value={filterValues[filter.name] || ''}
                                                onChange={(e) => handleFilterChange(filter.name, e.target.value)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            </PopoverContent>
                        </Popover>

                        {/* Select Filters */}
                        {selectFilters.map((filter) => (
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

                        {/* More Filters Button */}
                        <Button variant="outline" className="gap-2">
                            <Filter className="h-4 w-4" />
                            Más filtros
                            <ChevronDown className="h-4 w-4" />
                        </Button>

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
                                <Button variant="outline" size="sm" className="gap-2">
                                    <Filter className="h-4 w-4" />
                                    Filtrar
                                </Button>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {columns.map((column) => (
                                            <TableHead key={column.key} className={column.align === 'right' ? 'text-right' : ''}>
                                                {column.label}
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
