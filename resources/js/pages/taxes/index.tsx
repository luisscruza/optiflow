import { Calculator, Edit, Eye, MoreHorizontal, Plus, Search, Star, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedTaxes, type TaxFilters } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Impuestos',
        href: '/taxes',
    },
];

interface Props {
    taxes: PaginatedTaxes;
    filters: TaxFilters;
}

export default function TaxesIndex({ taxes, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [deletingTax, setDeletingTax] = useState<number | null>(null);

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/taxes', { search: value || undefined }, { preserveState: true, replace: true });
    };

    const handleDeleteTax = (taxId: number) => {
        if (deletingTax) return;

        setDeletingTax(taxId);
        router.delete(`/taxes/${taxId}`, {
            onFinish: () => setDeletingTax(null),
        });
    };

    const formatPercentage = (rate: number) => {
        return `${Number(rate).toLocaleString()}%`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestión de Impuestos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Gestión de Impuestos</h1>
                            <p className="text-muted-foreground">Administra las tasas de impuestos para tus productos y documentos</p>
                        </div>

                        <Button asChild>
                            <Link href="/taxes/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo Impuesto
                            </Link>
                        </Button>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Filtros</CardTitle>
                            <CardDescription>Busca y filtra los impuestos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center space-x-4">
                                <div className="relative flex-1">
                                    <Search className="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Buscar por nombre..."
                                        value={search}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Taxes Table */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Impuestos</CardTitle>
                            <CardDescription>Lista de todos los impuestos configurados</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nombre</TableHead>
                                            <TableHead className="text-right">Tasa</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {taxes.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={4} className="py-8 text-center">
                                                    <div className="flex flex-col items-center space-y-2">
                                                        <Calculator className="h-8 w-8 text-muted-foreground" />
                                                        <p className="text-muted-foreground">No se encontraron impuestos</p>
                                                        <Button asChild size="sm" variant="outline">
                                                            <Link href="/taxes/create">Crear tu primer impuesto</Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            taxes.data.map((tax) => (
                                                <TableRow key={tax.id}>
                                                    <TableCell className="font-medium">
                                                        <div className="flex items-center space-x-2">
                                                            <Calculator className="h-4 w-4 text-muted-foreground" />
                                                            <span>{tax.name}</span>
                                                            {tax.is_default && <Star className="h-3 w-3 fill-yellow-500 text-yellow-500" />}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right font-mono">{formatPercentage(tax.rate)}</TableCell>
                                                    <TableCell>
                                                        {tax.is_default ? (
                                                            <Badge variant="default">Predeterminado</Badge>
                                                        ) : (
                                                            <Badge variant="secondary">Activo</Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <DropdownMenu>
                                                            <DropdownMenuTrigger asChild>
                                                                <Button variant="ghost" size="sm">
                                                                    <MoreHorizontal className="h-4 w-4" />
                                                                </Button>
                                                            </DropdownMenuTrigger>
                                                            <DropdownMenuContent align="end">
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/taxes/${tax.id}`}>
                                                                        <Eye className="mr-2 h-3 w-3" />
                                                                        Ver detalles
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/taxes/${tax.id}/edit`}>
                                                                        <Edit className="mr-2 h-3 w-3" />
                                                                        Editar
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    className="text-destructive focus:text-destructive"
                                                                    onClick={() => handleDeleteTax(tax.id)}
                                                                    disabled={deletingTax === tax.id}
                                                                >
                                                                    <Trash2 className="mr-2 h-3 w-3" />
                                                                    {deletingTax === tax.id ? 'Eliminando...' : 'Eliminar'}
                                                                </DropdownMenuItem>
                                                            </DropdownMenuContent>
                                                        </DropdownMenu>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {taxes.last_page > 1 && (
                                <div className="flex items-center justify-between space-x-2 py-4">
                                    <div className="text-sm text-muted-foreground">
                                        Mostrando {taxes.from} a {taxes.to} de {taxes.total} resultados
                                    </div>
                                    <div className="flex space-x-2">
                                        {taxes.links.prev && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(taxes.links.prev!)}>
                                                Anterior
                                            </Button>
                                        )}
                                        {taxes.links.next && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(taxes.links.next!)}>
                                                Siguiente
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
