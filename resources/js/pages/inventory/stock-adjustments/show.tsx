import { ArrowLeft, ArrowRightLeft, Building2, Calendar, Package, RotateCcw, TrendingDown, TrendingUp, User } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedStockMovements, type Product, type ProductStock, type Workspace } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '#',
    },
    {
        title: 'Ajuste de inventario',
        href: '/stock-adjustments',
    },
    {
        title: 'Historial',
        href: '#',
    },
];

interface Props {
    product: Product;
    currentStock: ProductStock | null;
    stockHistory: PaginatedStockMovements;
    workspace: Workspace;
}

export default function StockAdjustmentsShow({ product, currentStock, stockHistory, workspace }: Props) {
    const formatQuantity = (quantity: number) => {
        return Number(quantity).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });
    };

    const getMovementIcon = (type: string) => {
        switch (type) {
            case 'add_quantity':
            case 'in':
            case 'transfer_in':
                return <TrendingUp className="h-4 w-4 text-green-600" />;
            case 'remove_quantity':
            case 'out':
            case 'transfer_out':
                return <TrendingDown className="h-4 w-4 text-red-600" />;
            case 'set_quantity':
            case 'adjustment':
                return <RotateCcw className="h-4 w-4 text-blue-600" />;
            default:
                return <Package className="h-4 w-4 text-muted-foreground" />;
        }
    };

    const getMovementBadge = (type: string) => {
        switch (type) {
            case 'initial':
                return <Badge variant="secondary">Inventario inicial</Badge>;
            case 'add_quantity':
                return <Badge variant="default">Agregar cantidad</Badge>;
            case 'remove_quantity':
                return <Badge variant="destructive">Quitar cantidad</Badge>;
            case 'set_quantity':
                return <Badge variant="outline">Establecer cantidad</Badge>;
            case 'transfer_in':
                return <Badge variant="default">Transferir entrada</Badge>;
            case 'transfer_out':
                return <Badge variant="destructive">Transferir salida</Badge>;
            default:
                return <Badge variant="outline">{type}</Badge>;
        }
    };

    const getStockStatus = (quantity: number, minimum: number) => {
        if (quantity <= 0) {
            return { label: 'Agotado', variant: 'destructive' as const };
        }
        if (quantity <= minimum) {
            return { label: 'Inventario bajo', variant: 'secondary' as const };
        }
        return { label: 'En inventario', variant: 'default' as const };
    };

    const renderWorkspaceTransfer = (movement: any) => {
        const hasFromWorkspace = movement.from_workspace;
        const hasToWorkspace = movement.to_workspace;

        if (!hasFromWorkspace && !hasToWorkspace) {
            return <span className="text-sm text-muted-foreground">-</span>;
        }

        if (movement.type === 'transfer_out' && hasToWorkspace) {
            return (
                <div className="flex items-center space-x-1 text-sm">
                    <Building2 className="h-3 w-3 text-muted-foreground" />
                    <span className="text-muted-foreground">Para:</span>
                    <span className="font-medium">{hasToWorkspace.name}</span>
                </div>
            );
        }

        if (movement.type === 'transfer_in' && hasFromWorkspace) {
            return (
                <div className="flex items-center space-x-1 text-sm">
                    <Building2 className="h-3 w-3 text-muted-foreground" />
                    <span className="text-muted-foreground">De:</span>
                    <span className="font-medium">{hasFromWorkspace.name}</span>
                </div>
            );
        }

        if (hasFromWorkspace && hasToWorkspace) {
            return (
                <div className="flex items-center space-x-1 text-sm">
                    <span className="font-medium">{hasFromWorkspace.name}</span>
                    <ArrowRightLeft className="h-3 w-3 text-muted-foreground" />
                    <span className="font-medium">{hasToWorkspace.name}</span>
                </div>
            );
        }

        return <span className="text-sm text-muted-foreground">-</span>;
    };

    const status = currentStock ? getStockStatus(currentStock.quantity, currentStock.minimum_quantity) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Stock History - ${product.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/stock-adjustments">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <h1 className="text-3xl font-bold tracking-tight">{product.name}</h1>
                            <p className="text-muted-foreground">
                                Historial de inventario para {product.sku} en {workspace.name}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href="/stock-adjustments/create">New Adjustment</Link>
                        </Button>
                    </div>

                    {/* Current Stock Overview */}
                    <div className="grid gap-4 md:grid-cols-3">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Current Stock</CardTitle>
                                <Package className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{currentStock ? formatQuantity(currentStock.quantity) : '0'}</div>
                                {status && (
                                    <Badge variant={status.variant} className="mt-2">
                                        {status.label}
                                    </Badge>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Minimum Quantity</CardTitle>
                                <TrendingDown className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{currentStock ? formatQuantity(currentStock.minimum_quantity) : '0'}</div>
                                <p className="text-xs text-muted-foreground">Reorder threshold</p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Movements</CardTitle>
                                <RotateCcw className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stockHistory.total}</div>
                                <p className="text-xs text-muted-foreground">All time transactions</p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Stock Movement History */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Movement History</CardTitle>
                            <CardDescription>Complete history of stock movements for this product</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Cantidad</TableHead>
                                            <TableHead>Transferencia de espacio</TableHead>
                                            <TableHead>Referencia</TableHead>
                                            <TableHead>Notas</TableHead>
                                            <TableHead>Creado por</TableHead>
                                            <TableHead>Fecha</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stockHistory.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-8 text-center">
                                                    <div className="flex flex-col items-center space-y-2">
                                                        <Package className="h-8 w-8 text-muted-foreground" />
                                                        <p className="text-muted-foreground">No stock movements found</p>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            stockHistory.data.map((movement) => (
                                                <TableRow key={movement.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            {getMovementIcon(movement.type)}
                                                            {getMovementBadge(movement.type)}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="font-mono">
                                                        <span
                                                            className={`font-semibold ${
                                                                movement.type.includes('remove') || movement.type.includes('out')
                                                                    ? 'text-red-600'
                                                                    : movement.type.includes('add') || movement.type.includes('in')
                                                                      ? 'text-green-600'
                                                                      : ''
                                                            }`}
                                                        >
                                                            {movement.type.includes('remove') || movement.type.includes('out') ? '-' : '+'}
                                                            {formatQuantity(movement.quantity)}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>{renderWorkspaceTransfer(movement)}</TableCell>
                                                    <TableCell className="text-muted-foreground">{movement.reference_number || '-'}</TableCell>
                                                    <TableCell className="text-muted-foreground">{movement.notes || '-'}</TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-1">
                                                            <User className="h-3 w-3" />
                                                            <span className="text-sm">{movement.created_by?.name || 'Unknown'}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        <div className="flex items-center space-x-1">
                                                            <Calendar className="h-3 w-3" />
                                                            <span className="text-sm">{new Date(movement.created_at).toLocaleString()}</span>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Pagination */}
                            {stockHistory.last_page > 1 && (
                                <div className="flex items-center justify-between space-x-2 py-4">
                                    <div className="text-sm text-muted-foreground">
                                        Showing {stockHistory.from} to {stockHistory.to} of {stockHistory.total} results
                                    </div>
                                    <div className="flex space-x-2">
                                        {stockHistory.links.prev && (
                                            <Button variant="outline" size="sm" onClick={() => (window.location.href = stockHistory.links.prev!)}>
                                                Anterior
                                            </Button>
                                        )}
                                        {stockHistory.links.next && (
                                            <Button variant="outline" size="sm" onClick={() => (window.location.href = stockHistory.links.next!)}>
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
