import { AlertTriangle, ArrowLeftRight, Edit, Eye, MoreHorizontal, Package, Plus, RotateCcw, Search, Trash2, TrendingUp } from 'lucide-react';
import { useState } from 'react';

import { create, destroy, edit, index, show } from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Paginator } from '@/components/ui/paginator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedProducts, type ProductFilters } from '@/types';
import { useCurrency } from '@/utils/currency';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Productos',
        href: index().url,
    },
];

interface Props {
    products: PaginatedProducts;
    filters: ProductFilters;
}

export default function ProductsIndex({ products, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [trackStock, setTrackStock] = useState(filters.track_stock);
    const [lowStock, setLowStock] = useState(filters.low_stock || false);
    const [deletingProduct, setDeletingProduct] = useState<number | null>(null);
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            index().url,
            { search: value || undefined, track_stock: trackStock, low_stock: lowStock || undefined },
            { preserveState: true, replace: true },
        );
    };

    const handleFilterChange = () => {
        router.get(
            index().url,
            { search: search || undefined, track_stock: trackStock, low_stock: lowStock || undefined },
            { preserveState: true, replace: true },
        );
    };

    const handleDeleteProduct = (productId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
            setDeletingProduct(productId);
            router.delete(destroy(productId).url, {
                onFinish: () => setDeletingProduct(null),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Productos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Productos</h1>
                        <p className="mt-2 text-gray-600 dark:text-gray-400">Gestiona tu catálogo de productos e inventario.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {can('view inventory') && (
                            <Button variant="outline" asChild>
                                <Link href="/stock-adjustments">
                                    <Package className="mr-2 h-4 w-4" />
                                    Gestión de inventario
                                </Link>
                            </Button>
                        )}
                        {can('create products') && (
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar producto
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="text-lg">Búsqueda y filtros</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-4 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label htmlFor="search" className="mb-2 block text-sm font-medium">
                                    Buscar productos
                                </label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                                    <Input
                                        id="search"
                                        placeholder="Buscar por nombre, SKU o descripción..."
                                        value={search}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="track_stock"
                                        checked={trackStock}
                                        onCheckedChange={(checked) => {
                                            setTrackStock(checked as boolean);
                                            setTimeout(handleFilterChange, 0);
                                        }}
                                    />
                                    <label htmlFor="track_stock" className="text-sm font-medium">
                                        Solo productos con inventario
                                    </label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="low_stock"
                                        checked={lowStock}
                                        onCheckedChange={(checked) => {
                                            setLowStock(checked as boolean);
                                            setTimeout(handleFilterChange, 0);
                                        }}
                                    />
                                    <label htmlFor="low_stock" className="text-sm font-medium">
                                        Solo inventario bajo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Productos Table */}
                {products.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Package className="mb-4 h-12 w-12 text-gray-400" />
                            <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">No se encontraron productos</h3>
                            <p className="mb-6 max-w-md text-center text-gray-600 dark:text-gray-400">
                                {filters.search
                                    ? 'Ningún producto coincide con tus criterios de búsqueda. Intenta ajustar tus filtros.'
                                    : 'Comienza agregando tu primer producto al catálogo.'}
                            </p>
                            <Button asChild>
                                <Link prefetch href={create().url}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar producto
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Productos ({products.total})</CardTitle>
                            <CardDescription>
                                Mostrando {products.from} a {products.to} de {products.total} productos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Producto</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Precio</TableHead>
                                        <TableHead>Inventario</TableHead>
                                        <TableHead>Impuesto</TableHead>
                                        <TableHead className="w-[70px]">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.map((product) => (
                                        <TableRow key={product.id}>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{product.name}</div>
                                                    {product.description && (
                                                        <div className="mt-1 line-clamp-2 text-sm text-gray-500">{product.description}</div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <code className="rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-800">{product.sku}</code>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{formatCurrency(product.price)}</div>
                                                    {product.cost && (
                                                        <div className="text-sm text-gray-500">Costo: {formatCurrency(product.cost)}</div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {product.track_stock ? (
                                                    product.stock_in_current_workspace ? (
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-medium">{product.stock_in_current_workspace.quantity}</span>
                                                            {product.stock_in_current_workspace.quantity <=
                                                                product.stock_in_current_workspace.minimum_quantity && (
                                                                <Badge variant="destructive" className="text-xs">
                                                                    <AlertTriangle className="mr-1 h-3 w-3" />
                                                                    Bajo
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <Badge variant="secondary">Sin datos de inventario</Badge>
                                                    )
                                                ) : (
                                                    <Badge variant="outline">Sin seguimiento</Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {product.default_tax ? (
                                                    <span className="text-sm">
                                                        {product.default_tax.name} ({product.default_tax.rate}%)
                                                    </span>
                                                ) : (
                                                    <span className="text-gray-500">Sin impuesto</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="sm">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {can('view products') && (
                                                            <DropdownMenuItem asChild>
                                                                <Link href={show(product.id).url}>
                                                                    <Eye className="mr-2 h-4 w-4" />
                                                                    Ver
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {can('edit products') && (
                                                            <DropdownMenuItem asChild>
                                                                <Link href={edit(product.id).url}>
                                                                    <Edit className="mr-2 h-4 w-4" />
                                                                    Editar
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {product.track_stock && can('view inventory') && (
                                                            <>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/stock-adjustments/${product.id}`}>
                                                                        <RotateCcw className="mr-2 h-4 w-4" />
                                                                        Historial de inventario
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                {can('adjust inventory') && (
                                                                    <>
                                                                        <DropdownMenuItem asChild>
                                                                            <Link href="/stock-adjustments/create" preserveState={false}>
                                                                                <TrendingUp className="mr-2 h-4 w-4" />
                                                                                Ajustar inventario
                                                                            </Link>
                                                                        </DropdownMenuItem>
                                                                        {!product.stock_in_current_workspace && (
                                                                            <DropdownMenuItem asChild>
                                                                                <Link href="/initial-stock/create" preserveState={false}>
                                                                                    <Package className="mr-2 h-4 w-4" />
                                                                                    Establecer inventario inicial
                                                                                </Link>
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                    </>
                                                                )}
                                                                {can('transfer inventory') && (
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href="/stock-transfers/create" preserveState={false}>
                                                                            <ArrowLeftRight className="mr-2 h-4 w-4" />
                                                                            Transferir inventario
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                )}
                                                            </>
                                                        )}
                                                        {can('delete products') && (
                                                            <>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    onClick={() => handleDeleteProduct(product.id)}
                                                                    className="text-red-600 dark:text-red-400"
                                                                    disabled={deletingProduct === product.id}
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                                    {deletingProduct === product.id ? 'Eliminando...' : 'Eliminar'}
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            <Paginator data={products} className="mt-6" />
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
