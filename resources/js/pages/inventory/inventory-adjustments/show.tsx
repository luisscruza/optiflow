import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Ajustes de inventario',
        href: '/inventory-adjustments',
    },
    {
        title: 'Detalle',
        href: '#',
    },
];

interface AdjustmentItem {
    id: number;
    adjustment_type: 'increment' | 'decrement';
    quantity: number | string;
    current_quantity: number | string;
    final_quantity: number | string;
    average_cost: number | string;
    total_adjusted: number | string;
    product?: {
        id: number;
        name: string;
        sku: string;
    };
}

interface InventoryAdjustment {
    id: number;
    adjustment_date: string;
    notes?: string | null;
    total_adjusted: number | string;
    workspace?: {
        id: number;
        name: string;
    };
    created_by?: {
        id: number;
        name: string;
    };
    items: AdjustmentItem[];
}

interface Props {
    adjustment: InventoryAdjustment;
}

export default function InventoryAdjustmentsShow({ adjustment }: Props) {
    const { format: formatCurrency } = useCurrency();

    const formatDate = (value: string): string => {
        return new Date(value).toLocaleDateString('es-DO');
    };

    const parseNumber = (value: number | string): number => {
        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ajuste de inventario ${adjustment.id}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/inventory-adjustments">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <h1 className="text-3xl font-semibold tracking-tight text-foreground">Ajuste de inventario {adjustment.id}</h1>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-4">
                            <CardTitle className="text-3xl">Detalle del ajuste</CardTitle>
                            <p className="text-3xl font-semibold tracking-tight text-foreground">No. {adjustment.id}</p>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Almacen</p>
                                <p className="text-lg font-medium text-foreground">{adjustment.workspace?.name ?? '-'}</p>
                            </div>

                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Fecha</p>
                                <p className="text-lg font-medium text-foreground">{formatDate(adjustment.adjustment_date)}</p>
                            </div>

                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Creado por</p>
                                <p className="text-lg font-medium text-foreground">{adjustment.created_by?.name ?? '-'}</p>
                            </div>
                        </div>

                        <div className="space-y-2 border-b pb-6">
                            <p className="text-sm text-muted-foreground">Observaciones</p>
                            <p className="text-base text-foreground">{adjustment.notes || '-'}</p>
                        </div>

                        <div className="space-y-4">
                            <h2 className="text-2xl font-semibold text-foreground">Productos de venta</h2>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Productos</TableHead>
                                            <TableHead className="text-right">Cantidad actual</TableHead>
                                            <TableHead>Tipo de ajuste</TableHead>
                                            <TableHead className="text-right">Cantidad</TableHead>
                                            <TableHead className="text-right">Costo por unidad</TableHead>
                                            <TableHead className="text-right">Cantidad final</TableHead>
                                            <TableHead className="text-right">Total ajustado</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {adjustment.items.length > 0 ? (
                                            adjustment.items.map((item) => {
                                                const lineTotal = parseNumber(item.total_adjusted);
                                                const isNegative = lineTotal < 0;

                                                return (
                                                    <TableRow key={item.id}>
                                                        <TableCell>
                                                            <div className="font-medium text-foreground">{item.product?.name ?? '-'}</div>
                                                            <p className="text-sm text-muted-foreground">{item.product?.sku ?? '-'}</p>
                                                        </TableCell>
                                                        <TableCell className="text-right">{parseNumber(item.current_quantity)}</TableCell>
                                                        <TableCell>{item.adjustment_type === 'increment' ? 'Incremento' : 'Disminucion'}</TableCell>
                                                        <TableCell className="text-right">{parseNumber(item.quantity)}</TableCell>
                                                        <TableCell className="text-right">{formatCurrency(parseNumber(item.average_cost))}</TableCell>
                                                        <TableCell className="text-right">{parseNumber(item.final_quantity)}</TableCell>
                                                        <TableCell
                                                            className={`text-right font-medium ${isNegative ? 'text-red-600' : 'text-emerald-600'}`}
                                                        >
                                                            {formatCurrency(lineTotal)}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                                    Este ajuste no tiene lineas de productos.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            <Separator />

                            <div className="flex justify-end">
                                <p className="text-4xl font-semibold tracking-tight text-foreground">
                                    Total {formatCurrency(parseNumber(adjustment.total_adjusted))}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
