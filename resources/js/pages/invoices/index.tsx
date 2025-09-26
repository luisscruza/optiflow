import { Head, Link, router } from '@inertiajs/react';
import { Calendar, DollarSign, Edit, Eye, Filter, Plus, Printer, Search, Trash2, User } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Invoice, type InvoiceFilters, type PaginatedInvoices } from '@/types';
import { useCurrency } from '@/utils/currency';
import { Paginator } from '@/components/ui/paginator';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturas',
        href: '/invoices',
    },
];

interface Props {
    invoices: PaginatedInvoices;
    filters: InvoiceFilters;
}

export default function InvoicesIndex({ invoices, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const { format: formatCurrency } = useCurrency();

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const statusValue = status === 'all' ? undefined : status;
        router.get(
            '/invoices',
            { search, status: statusValue },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setStatus('all');
        router.get(
            '/invoices',
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (invoiceId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta factura?')) {
            router.delete(`/invoices/${invoiceId}`);
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturas" />

            <div className="px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Facturas</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Gestiona tus facturas y realiza un seguimiento de los pagos.
                        </p>
                    </div>

                    <div className="flex space-x-3">
                        <Button asChild className="bg-primary hover:bg-primary/90">
                            <Link href="/invoices/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva factura
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Search and Filters */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                        <CardDescription>Busca facturas por cliente, número de factura o estado</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    type="text"
                                    placeholder="Buscar cliente..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full"
                                />
                            </div>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger className="w-48">
                                    <SelectValue placeholder="Estado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los estados</SelectItem>
                                    <SelectItem value="draft">Borrador</SelectItem>
                                    <SelectItem value="sent">Enviado</SelectItem>
                                    <SelectItem value="paid">Cobrada</SelectItem>
                                    <SelectItem value="overdue">Vencida</SelectItem>
                                    <SelectItem value="cancelled">Cancelada</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit" variant="outline">
                                <Search className="mr-2 h-4 w-4" />
                                Filtrar
                            </Button>
                            {(search || status !== 'all') && (
                                <Button type="button" variant="outline" onClick={handleClearFilters}>
                                    Limpiar
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Invoices Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de facturas</CardTitle>
                        <CardDescription>
                            {invoices.total === 0
                                ? 'No se encontraron facturas.'
                                : `Mostrando ${invoices.from} - ${invoices.to} de ${invoices.total} facturas`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invoices.data.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron facturas</div>
                                <Button asChild>
                                    <Link href="/invoices/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear primera factura
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-20"># Interno</TableHead>
                                            <TableHead>NCF/Número</TableHead>
                                            <TableHead>Cliente</TableHead>
                                            <TableHead>Creación</TableHead>
                                            <TableHead>Vencimiento</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                            <TableHead className="text-right">Por cobrar</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoices.data.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell className="font-medium text-gray-500">
                                                    {invoice.id}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {invoice.document_number}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{invoice.contact.name}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        {formatDate(invoice.issue_date)}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className={`text-sm`}>
                                                        {formatDate(invoice.due_date)}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    {formatCurrency(invoice.total_amount)}
                                                </TableCell>
                                                <TableCell className={`text-right font-medium ${invoice.amount_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'}`}>
                                                    {formatCurrency(invoice.amount_due)}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge
                                                        variant={invoice.status_config.variant}
                                                        className={invoice.status_config.className || undefined}
                                                    >
                                                        {invoice.status_config.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    { invoice.status !== 'paid' && (
                                                        <Button variant="ghost" size="sm">
                                                            <DollarSign className="mr-2 h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                ⋮
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/invoices/${invoice.id}`}>
                                                                    <Eye className="mr-2 h-4 w-4" />
                                                                    Ver detalles
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/invoices/${invoice.id}/edit`}>
                                                                    <Edit className="mr-2 h-4 w-4" />
                                                                    Editar
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem>
                                                                <a 
                                                                    href={`/invoices/${invoice.id}/pdf`} 
                                                                    target="_blank" 
                                                                    rel="noopener noreferrer"
                                                                    className="flex items-center w-full"
                                                                >
                                                                    <Printer className="mr-2 h-4 w-4" />
                                                                    Ver PDF
                                                                </a>
                                                            </DropdownMenuItem>
                                                            {invoice.status === 'draft' && (
                                                                <DropdownMenuItem
                                                                    onClick={() => handleDelete(invoice.id)}
                                                                    className="text-red-600 dark:text-red-400"
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                                    Eliminar
                                                                </DropdownMenuItem>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        <Paginator data={invoices} className="mt-6" />
                    
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}