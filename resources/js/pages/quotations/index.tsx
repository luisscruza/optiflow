import { Head, Link, router } from '@inertiajs/react';
import { Building2, Edit, Eye, FileText, Filter, Plus, Printer, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Document, type InvoiceFilters, type PaginatedInvoices } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cotizaciones',
        href: '/quotations',
    },
];

interface Props {
    quotations: PaginatedInvoices;
    filters: InvoiceFilters;
}

export default function QuotationsIndex({ quotations, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();

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

    const handleDelete = (quotationId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta cotización?')) {
            router.delete(`/quotations/${quotationId}`);
        }
    };

    const handleConvertToInvoice = (quotationId: number) => {
        if (confirm('¿Estás seguro de que deseas convertir esta cotización a factura? Esta acción no se puede deshacer.')) {
            router.post(`/quotations/${quotationId}/convert-to-invoice`);
        }
    };

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { label: 'Borrador', variant: 'secondary' as const, className: '' },
            sent: { label: 'Enviado', variant: 'default' as const, className: '' },
            accepted: { label: 'Aceptada', variant: 'default' as const, className: 'bg-green-100 text-green-800 border-green-200' },
            converted: { label: 'Convertida', variant: 'default' as const, className: 'bg-blue-100 text-blue-800 border-blue-200' },
            expired: { label: 'Vencida', variant: 'destructive' as const, className: '' },
            cancelled: { label: 'Cancelada', variant: 'outline' as const, className: '' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft;

        return (
            <Badge variant={config.variant} className={config.className || undefined}>
                {config.label}
            </Badge>
        );
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const isExpired = (dueDate: string, status: string) => {
        const today = new Date();
        const due = new Date(dueDate);
        return due < today && status !== 'accepted' && status !== 'cancelled';
    };

    const getBalanceAmount = (quotation: Document) => {
        if (quotation.status === 'accepted') {
            return 0;
        }
        return quotation.total_amount;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizaciones" />

            <div className="px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Cotizaciones</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona tus cotizaciones y realiza un seguimiento de las propuestas.</p>
                    </div>

                    <div className="flex space-x-3">
                        {can('create quotations') && (
                            <Button asChild className="bg-primary hover:bg-primary/90">
                                <Link href="/quotations/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva cotización
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Search and Filters */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                        <CardDescription>Busca cotizaciones por cliente, número de cotización o estado</CardDescription>
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
                                    <SelectItem value="accepted">Aceptada</SelectItem>
                                    <SelectItem value="expired">Vencida</SelectItem>
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

                {/* Quotations Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de cotizaciones</CardTitle>
                        <CardDescription>
                            {quotations.total === 0
                                ? 'No se encontraron cotizaciones.'
                                : `Mostrando ${quotations.from} - ${quotations.to} de ${quotations.total} cotizaciones`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {quotations.data.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron cotizaciones</div>
                                {can('create quotations') && (
                                    <Button asChild>
                                        <Link href="/quotations/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Crear primera cotización
                                        </Link>
                                    </Button>
                                )}
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
                                            <TableHead className="text-right">Monto</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {quotations.data.map((quotation) => (
                                            <TableRow key={quotation.id}>
                                                <TableCell className="font-medium text-gray-500">{quotation.id}</TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{quotation.document_number}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{quotation.contact.name}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">{formatDate(quotation.issue_date)}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div
                                                        className={`text-sm ${
                                                            isExpired(quotation.due_date, quotation.status)
                                                                ? 'font-medium text-red-600'
                                                                : 'text-gray-600'
                                                        }`}
                                                    >
                                                        {formatDate(quotation.due_date)}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">{formatCurrency(quotation.total_amount)}</TableCell>
                                                <TableCell className="text-right font-medium">
                                                    <span className={getBalanceAmount(quotation) > 0 ? 'text-red-600' : 'text-green-600'}>
                                                        {formatCurrency(getBalanceAmount(quotation))}
                                                    </span>
                                                </TableCell>
                                                <TableCell>{getStatusBadge(quotation.status)}</TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                ⋮
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            {can('view quotations') && (
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/quotations/${quotation.id}`}>
                                                                        <Eye className="mr-2 h-4 w-4" />
                                                                        Ver detalles
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                            )}
                                                            {can('edit quotations') && (
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/quotations/${quotation.id}/edit`}>
                                                                        <Edit className="mr-2 h-4 w-4" />
                                                                        Editar
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                            )}
                                                            <DropdownMenuItem>
                                                                <a
                                                                    href={`/quotations/${quotation.id}/pdf`}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="flex w-full items-center"
                                                                >
                                                                    <Printer className="mr-2 h-4 w-4" />
                                                                    Ver PDF
                                                                </a>
                                                            </DropdownMenuItem>
                                                            {quotation.status !== 'converted' && (
                                                                <DropdownMenuItem
                                                                    onClick={() => handleConvertToInvoice(quotation.id)}
                                                                    className="text-blue-600 dark:text-blue-400"
                                                                >
                                                                    <FileText className="mr-2 h-4 w-4" />
                                                                    Convertir a factura
                                                                </DropdownMenuItem>
                                                            )}
                                                            {can('delete quotations') && (
                                                                <DropdownMenuItem
                                                                    onClick={() => handleDelete(quotation.id)}
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

                        {/* Pagination */}
                        {quotations.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between">
                                <div className="text-sm text-gray-600 dark:text-gray-400">
                                    Página {quotations.current_page} de {quotations.last_page}
                                </div>
                                <div className="flex space-x-2">
                                    {quotations.links.prev && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={quotations.links.prev}>Anterior</Link>
                                        </Button>
                                    )}
                                    {quotations.links.next && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={quotations.links.next}>Siguiente</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
