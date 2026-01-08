import { Head, router } from '@inertiajs/react';
import { Download, Filter, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Report {
    id: number;
    type: string;
    name: string;
    description: string;
    group: string;
    groupLabel: string;
}

interface CustomerData {
    customer_id: number;
    customer_name: string;
    total_invoices: number;
    total_sales: number;
    total_taxes: number;
    total_amount: number;
}

interface Summary {
    total_customers: number;
    total_invoices: number;
    total_sales: number;
    total_amount: number;
}

interface ReportData {
    customers?: CustomerData[];
    summary?: Summary;
}

interface Filters {
    start_date?: string;
    end_date?: string;
}

interface Props {
    report: Report;
    data: ReportData;
    filters: Filters;
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

export default function ReportShow({ report, data, filters }: Props) {
    const formatCurrency = useCurrency();
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const [showFilters, setShowFilters] = useState(false);

    const handleFilter: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(`/reports/${report.type}`, { start_date: startDate, end_date: endDate }, { preserveState: true, preserveScroll: true });
    };

    const handleExport = () => {
        // TODO: Implement export functionality
        console.log('Export report');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(report.name, report.groupLabel, report.group)}>
            <Head title={report.name} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{report.name}</h1>
                            <p className="text-muted-foreground">{report.description}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => setShowFilters(!showFilters)}>
                                <Filter className="h-4 w-4" />
                                Filtros
                            </Button>
                            <Button onClick={handleExport}>
                                <Download className="h-4 w-4" />
                                Exportar
                            </Button>
                        </div>
                    </div>

                    {/* Filters */}
                    {showFilters && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Filtros</CardTitle>
                                <CardDescription>Filtra los datos del reporte por fecha</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleFilter} className="flex flex-col gap-4 sm:flex-row sm:items-end">
                                    <div className="flex-1 space-y-2">
                                        <Label htmlFor="start_date">Fecha de inicio</Label>
                                        <Input id="start_date" type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} />
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <Label htmlFor="end_date">Fecha de fin</Label>
                                        <Input id="end_date" type="date" value={endDate} onChange={(e) => setEndDate(e.target.value)} />
                                    </div>
                                    <Button type="submit">Aplicar filtros</Button>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* Summary Cards */}
                    {data.summary && (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardDescription>Total Clientes</CardDescription>
                                    <CardTitle className="text-2xl">{data.summary.total_customers}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardDescription>Total Facturas</CardDescription>
                                    <CardTitle className="text-2xl">{data.summary.total_invoices}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardDescription>Total Ventas</CardDescription>
                                    <CardTitle className="text-2xl">{formatCurrency(data.summary.total_sales)}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardDescription>Monto Total</CardDescription>
                                    <CardTitle className="text-2xl">{formatCurrency(data.summary.total_amount)}</CardTitle>
                                </CardHeader>
                            </Card>
                        </div>
                    )}

                    {/* Data Table for Sales by Customer */}
                    {data.customers && data.customers.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalle por Cliente</CardTitle>
                                <CardDescription>Ventas detalladas por cada cliente</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Cliente</TableHead>
                                            <TableHead className="text-right">Facturas</TableHead>
                                            <TableHead className="text-right">Ventas</TableHead>
                                            <TableHead className="text-right">Impuestos</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.customers.map((customer) => (
                                            <TableRow key={customer.customer_id}>
                                                <TableCell className="font-medium">{customer.customer_name}</TableCell>
                                                <TableCell className="text-right">{customer.total_invoices}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(customer.total_sales)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(customer.total_taxes)}</TableCell>
                                                <TableCell className="text-right font-semibold">{formatCurrency(customer.total_amount)}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}

                    {/* Empty State */}
                    {data.customers && data.customers.length === 0 && (
                        <Card>
                            <CardContent className="flex min-h-[300px] flex-col items-center justify-center py-12">
                                <Search className="h-12 w-12 text-muted-foreground/50" />
                                <h3 className="mt-4 text-lg font-semibold">No hay datos disponibles</h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    No se encontraron datos para el período seleccionado. Intenta ajustar los filtros.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
