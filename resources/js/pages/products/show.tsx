import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, BarChart3, DollarSign, Edit, Package, RotateCcw, Tag, Trash2, TrendingUp } from 'lucide-react';
import { type ReactNode } from 'react';

import { edit, index } from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Product, type StockMovement } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Productos',
        href: index().url,
    },
    {
        title: 'Detalles del producto',
        href: '#',
    },
];

type ProductDetails = Product & {
    stock_movements?: StockMovement[];
    allow_negative_stock?: boolean;
    unit?: string | null;
    product_category_id?: number | null;
    product_category?: {
        id: number;
        name: string;
    } | null;
};

interface Props {
    product: ProductDetails;
    workspace_stocks: Array<{
        workspace_id: number;
        workspace_name: string;
        initial_quantity: number;
        current_quantity: number;
        minimum_quantity: number;
        maximum_quantity: number | null;
    }>;
}

function DetailItem({ label, value, className }: { label: string; value: ReactNode; className?: string }) {
    return (
        <div className={cn('space-y-2 border-b pb-4', className)}>
            <p className="text-sm font-medium text-muted-foreground">{label}</p>
            <div className="text-base font-semibold text-foreground">{value}</div>
        </div>
    );
}

export default function ProductsShow({ product, workspace_stocks }: Props) {
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();

    const formatQuantity = (value: number) => {
        const isWholeNumber = Number.isInteger(value);

        return isWholeNumber ? value.toString() : value.toFixed(2);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getStockStatus = () => {
        if (!product.track_stock) {
            return { status: 'not_tracked', label: 'Sin rastreo', variant: 'secondary' as const };
        }

        const stock = product.stock_in_current_workspace;
        if (!stock) {
            return { status: 'no_data', label: 'Sin datos de stock', variant: 'destructive' as const };
        }

        if (stock.quantity <= 0) {
            return { status: 'out_of_stock', label: 'Agotado', variant: 'destructive' as const };
        }

        if (stock.quantity <= stock.minimum_quantity) {
            return { status: 'low_stock', label: 'Stock bajo', variant: 'outline' as const };
        }

        return { status: 'in_stock', label: 'Disponible', variant: 'default' as const };
    };

    const stockStatus = getStockStatus();
    const taxRate = Number(product.default_tax?.rate ?? 0);
    const basePrice = Number(product.price ?? 0);
    const priceWithoutTax = basePrice;
    const taxAmount = basePrice * (taxRate / 100);
    const priceWithTax = basePrice + taxAmount;
    const categoryLabel =
        product.product_category?.name ?? (product.product_category_id ? `Categoría #${product.product_category_id}` : 'Sin categoría');
    const productTypeLabel = product.track_stock ? 'Producto' : 'Servicio';
    const unitLabel = product.unit || 'Unidad';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${product.name} - Detalles del producto`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{product.name}</h1>
                            <p className="text-gray-600 dark:text-gray-400">
                                SKU: <code className="rounded bg-gray-100 px-2 py-1 text-sm dark:bg-gray-800">{product.sku}</code>
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {product.track_stock && can('view inventory') && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={`/stock-adjustments/${product.id}`}>
                                        <RotateCcw className="mr-2 h-4 w-4" />
                                        Historial de stock
                                    </Link>
                                </Button>
                                {can('adjust inventory') && (
                                    <>
                                        <Button variant="outline" asChild>
                                            <Link href="/stock-adjustments/create">
                                                <TrendingUp className="mr-2 h-4 w-4" />
                                                Ajustar stock
                                            </Link>
                                        </Button>
                                        {!product.stock_in_current_workspace && (
                                            <Button variant="outline" asChild>
                                                <Link href="/initial-stock/create">
                                                    <Package className="mr-2 h-4 w-4" />
                                                    Establecer inventario inicial
                                                </Link>
                                            </Button>
                                        )}
                                    </>
                                )}
                                {can('transfer inventory') && (
                                    <Button variant="outline" asChild>
                                        <Link href="/stock-transfers/create">
                                            <ArrowLeftRight className="mr-2 h-4 w-4" />
                                            Transferir stock
                                        </Link>
                                    </Button>
                                )}
                            </>
                        )}
                        {can('edit products') && (
                            <Button asChild>
                                <Link href={edit(product.id).url}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar producto
                                </Link>
                            </Button>
                        )}
                        {can('delete products') && (
                            <Button
                                variant="destructive"
                                onClick={() => {
                                    if (confirm('¿Seguro que deseas eliminar este producto? Esta acción no se puede deshacer.')) {
                                        // Handle delete
                                    }
                                }}
                            >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="space-y-6 lg:col-span-2">
                        {/* Basic Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Package className="h-5 w-5" />
                                    Información del producto
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-8">
                                <div className="grid gap-x-6 gap-y-7 md:grid-cols-2">
                                    <DetailItem label="Código" value={product.id} />
                                    <DetailItem label="Referencia" value={product.sku || '—'} />
                                    <DetailItem label="Categoría" value={categoryLabel} />
                                    <DetailItem label="Tipo de producto" value={productTypeLabel} />
                                    <DetailItem label="Unidad de medida" value={unitLabel} />
                                    <DetailItem
                                        label="Descripción"
                                        value={product.description || 'Sin descripción disponible.'}
                                        className="md:col-span-2"
                                    />
                                </div>

                                <Separator />

                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="flex items-start gap-3 rounded-lg border border-emerald-200/60 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-900/20">
                                        <Package
                                            className={cn(
                                                'mt-0.5 h-5 w-5',
                                                product.track_stock ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground',
                                            )}
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-foreground">Producto inventariable</p>
                                            <p
                                                className={cn(
                                                    'text-sm',
                                                    product.track_stock ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted-foreground',
                                                )}
                                            >
                                                {product.track_stock ? 'Activado' : 'Desactivado'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-3 rounded-lg border border-emerald-200/60 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-900/20">
                                        <ArrowLeftRight
                                            className={cn(
                                                'mt-0.5 h-5 w-5',
                                                product.allow_negative_stock ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground',
                                            )}
                                        />
                                        <div>
                                            <p className="text-sm font-medium text-foreground">Venta en negativo</p>
                                            <p
                                                className={cn(
                                                    'text-sm',
                                                    product.allow_negative_stock ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted-foreground',
                                                )}
                                            >
                                                {product.allow_negative_stock ? 'Activado' : 'Desactivado'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {product.track_stock && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Package className="h-5 w-5" />
                                        Stock por almacenes
                                    </CardTitle>
                                    <CardDescription>Disponibilidad actual por cada sucursal o almacén.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Almacenes</TableHead>
                                                <TableHead className="text-right">Cantidad inicial</TableHead>
                                                <TableHead className="text-right">Cantidad actual</TableHead>
                                                <TableHead className="text-right">Cantidad mínima</TableHead>
                                                <TableHead className="text-right">Cantidad máxima</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {workspace_stocks.length > 0 ? (
                                                workspace_stocks.map((stock) => (
                                                    <TableRow key={stock.workspace_id}>
                                                        <TableCell className="font-medium">{stock.workspace_name}</TableCell>
                                                        <TableCell className="text-right text-muted-foreground">
                                                            {formatQuantity(stock.initial_quantity)}
                                                        </TableCell>
                                                        <TableCell
                                                            className={cn(
                                                                'text-right font-semibold',
                                                                stock.current_quantity < 0 && 'text-red-600 dark:text-red-400',
                                                            )}
                                                        >
                                                            {formatQuantity(stock.current_quantity)}
                                                        </TableCell>
                                                        <TableCell className="text-right text-muted-foreground">
                                                            {formatQuantity(stock.minimum_quantity)}
                                                        </TableCell>
                                                        <TableCell className="text-right text-muted-foreground">
                                                            {stock.maximum_quantity !== null ? formatQuantity(stock.maximum_quantity) : '—'}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                                        No hay almacenes disponibles para mostrar.
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        )}

                        {/* Stock Movements */}
                        {product.track_stock && product.stock_movements && product.stock_movements.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <BarChart3 className="h-5 w-5" />
                                        Movimientos recientes de inventario
                                    </CardTitle>
                                    <CardDescription>Últimas transacciones de inventario para este producto</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Fecha</TableHead>
                                                <TableHead>Tipo</TableHead>
                                                <TableHead>Cantidad</TableHead>
                                                <TableHead>Referencia</TableHead>
                                                <TableHead>Notas</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {product.stock_movements.slice(0, 10).map((movement) => (
                                                <TableRow key={movement.id}>
                                                    <TableCell>{formatDate(movement.created_at)}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={movement.type === 'in' ? 'default' : 'secondary'}>
                                                            {movement.type.toUpperCase()}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className={movement.type === 'in' ? 'text-green-600' : 'text-red-600'}>
                                                        {movement.type === 'in' ? '+' : '-'}
                                                        {movement.quantity}
                                                    </TableCell>
                                                    <TableCell>
                                                        {movement.reference_number ? (
                                                            <code className="text-sm">{movement.reference_number}</code>
                                                        ) : (
                                                            <span className="text-gray-400">—</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="max-w-[200px] truncate">{movement.notes || '—'}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Stock Information */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <DollarSign className="h-5 w-5" />
                                    Precio y costo
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="flex aspect-square max-h-72 items-center justify-center rounded-xl border border-dashed border-border bg-muted/30">
                                    <Tag className="h-16 w-16 text-muted-foreground/40" />
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Precio total</p>
                                        <div className="mt-2 flex items-end justify-between gap-3">
                                            <p className="text-3xl font-semibold tracking-tight">{formatCurrency(priceWithTax)}</p>
                                            <span className="pb-1 text-sm font-medium text-muted-foreground">DOP</span>
                                        </div>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Precio sin impuesto</p>
                                            <p className="mt-1 text-lg font-semibold text-foreground">{formatCurrency(priceWithoutTax)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Impuesto</p>
                                            <p className="mt-1 text-lg font-semibold text-foreground">
                                                {product.default_tax ? `${product.default_tax.name} (${product.default_tax.rate}%)` : 'Sin impuesto'}
                                            </p>
                                            {taxRate > 0 && <p className="text-sm text-muted-foreground">{formatCurrency(taxAmount)}</p>}
                                        </div>
                                    </div>

                                    <Separator />

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p className="text-sm text-muted-foreground">Costo por unidad</p>
                                            <p className="mt-1 text-lg font-semibold text-foreground">
                                                {product.cost ? formatCurrency(product.cost) : 'No definido'}
                                            </p>
                                        </div>
                                        <div className="self-end sm:text-right">
                                            <span className="text-sm font-medium text-emerald-600 dark:text-emerald-400">Cargar costo promedio</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Quick Stats */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <TrendingUp className="h-5 w-5" />
                                    Estado del producto
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600 dark:text-gray-400">Inventario:</span>
                                    <Badge variant={stockStatus.variant}>{stockStatus.label}</Badge>
                                </div>
                                {product.track_stock && product.stock_in_current_workspace && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Unidades en stock:</span>
                                        <span className="text-sm font-medium">{product.stock_in_current_workspace.quantity}</span>
                                    </div>
                                )}
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600 dark:text-gray-400">Creado:</span>
                                    <span className="text-sm font-medium">{formatDate(product.created_at)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600 dark:text-gray-400">Última actualización:</span>
                                    <span className="text-sm font-medium">{formatDate(product.updated_at)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-gray-600 dark:text-gray-400">Rastreo de stock:</span>
                                    <Badge variant={product.track_stock ? 'default' : 'secondary'}>{product.track_stock ? 'Sí' : 'No'}</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
