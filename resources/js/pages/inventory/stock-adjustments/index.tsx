import { Calendar, Eye, Package, Plus, Search } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedStockAdjustments, type Workspace } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '#',
    },
    {
        title: 'Ajuste de inventario',
        href: '/stock-adjustments',
    },
];

interface Props {
    stockAdjustments: PaginatedStockAdjustments;
    workspace: Workspace;
}

export default function StockAdjustmentsIndex({ stockAdjustments, workspace }: Props) {
    const [search, setSearch] = useState('');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/stock-adjustments', { search: value || undefined }, { preserveState: true, replace: true });
    };

    const formatQuantity = (quantity: number) => {
        return Number(quantity).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
    };

    const getStockStatus = (quantity: number, minimum: number) => {
        if (quantity <= 0) {
            return { label: 'Sin stock', variant: 'destructive' as const };
        }
        if (quantity <= minimum) {
            return { label: 'Stock bajo', variant: 'secondary' as const };
        }
        return { label: 'En stock', variant: 'default' as const };
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajuste de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Ajuste de inventario</h1>
                            <p className="text-muted-foreground">Gestiona y rastrea ajustes de niveles de stock para tus productos</p>
                        </div>

                        <Button asChild>
                            <Link href="/stock-adjustments/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo ajuste
                            </Link>
                        </Button>
                    </div>

                    {/* Filters */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Niveles de stock actuales</CardTitle>
                            <CardDescription>Resumen de niveles de stock de productos en {workspace.name}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="mb-6 flex items-center space-x-4">
                                <div className="relative flex-1">
                                    <Search className="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Buscar productos..."
                                        value={search}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        className="pl-8"
                                    />
                                </div>
                            </div>

                            {/* Ajuste de inventario Table */}
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Producto</TableHead>
                                            <TableHead>SKU</TableHead>
                                            <TableHead className="text-right">Stock actual</TableHead>
                                            <TableHead className="text-right">Mínimo</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead>Última actualización</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stockAdjustments.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-8 text-center">
                                                    <div className="flex flex-col items-center space-y-2">
                                                        <Package className="h-8 w-8 text-muted-foreground" />
                                                        <p className="text-muted-foreground">No se encontraron productos con stock</p>
                                                        <Button asChild size="sm" variant="outline">
                                                            <Link href="/products/create">Agrega tu primer producto</Link>
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            stockAdjustments.data.map((stock) => {
                                                const status = getStockStatus(stock.quantity, stock.minimum_quantity);

                                                return (
                                                    <TableRow key={stock.id}>
                                                        <TableCell className="font-medium">
                                                            <div className="flex items-center space-x-2">
                                                                <Package className="h-4 w-4 text-muted-foreground" />
                                                                <span>{stock.product?.name}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">{stock.product?.sku}</TableCell>
                                                        <TableCell className="text-right font-mono">{formatQuantity(stock.quantity)}</TableCell>
                                                        <TableCell className="text-right font-mono text-muted-foreground">
                                                            {formatQuantity(stock.minimum_quantity)}
                                                        </TableCell>
                                                        <TableCell>
                                                            <Badge variant={status.variant}>{status.label}</Badge>
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">
                                                            <div className="flex items-center space-x-1">
                                                                <Calendar className="h-3 w-3" />
                                                                <span className="text-sm">{new Date(stock.updated_at).toLocaleDateString()}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            <Button asChild size="sm" variant="outline">
                                                                <Link href={`/stock-adjustments/${stock.product?.id}`}>
                                                                    <Eye className="mr-1 h-3 w-3" />
                                                                    Ver historial
                                                                </Link>
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {stockAdjustments.last_page > 1 && (
                                <div className="flex items-center justify-between space-x-2 py-4">
                                    <div className="text-sm text-muted-foreground">
                                        Mostrando {stockAdjustments.from} a {stockAdjustments.to} de {stockAdjustments.total} resultados
                                    </div>
                                    <div className="flex space-x-2">
                                        {stockAdjustments.links.prev && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(stockAdjustments.links.prev!)}>
                                                Anterior
                                            </Button>
                                        )}
                                        {stockAdjustments.links.next && (
                                            <Button variant="outline" size="sm" onClick={() => router.get(stockAdjustments.links.next!)}>
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
