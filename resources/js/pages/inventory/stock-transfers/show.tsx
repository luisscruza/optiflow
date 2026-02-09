import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowLeftRight, Building2, Package, User } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type StockMovement, type Workspace } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Transferencias de inventario',
        href: '/stock-transfers',
    },
    {
        title: 'Detalle',
        href: '#',
    },
];

interface Props {
    transfer: StockMovement;
    workspace: Workspace;
}

export default function StockTransfersShow({ transfer, workspace }: Props) {
    const isIncoming = transfer.to_workspace_id === workspace.id;
    const transferNote = transfer.notes ?? transfer.note;

    const formatDate = (value: string): string => {
        return new Date(value).toLocaleDateString('es-DO');
    };

    const formattedQuantity = new Intl.NumberFormat('es-DO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(transfer.quantity || 0));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Transferencia ${transfer.reference_number || transfer.id}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <Button variant="outline" asChild>
                        <Link href="/stock-transfers">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>
                    <h1 className="text-3xl font-semibold tracking-tight text-foreground">
                        Transferencia {transfer.reference_number || `TR-${transfer.id}`}
                    </h1>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between gap-4">
                            <CardTitle className="text-3xl">Detalle de la transferencia</CardTitle>
                            <Badge variant={isIncoming ? 'default' : 'destructive'}>{isIncoming ? 'Entrante' : 'Saliente'}</Badge>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Producto</p>
                                <p className="text-lg font-medium text-foreground">{transfer.product?.name ?? '-'}</p>
                                <p className="text-sm text-muted-foreground">SKU: {transfer.product?.sku ?? '-'}</p>
                            </div>

                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Fecha</p>
                                <p className="text-lg font-medium text-foreground">{formatDate(transfer.created_at)}</p>
                            </div>

                            <div className="space-y-2 border-b pb-4">
                                <p className="text-sm text-muted-foreground">Creado por</p>
                                <p className="flex items-center gap-2 text-lg font-medium text-foreground">
                                    <User className="h-4 w-4 text-muted-foreground" />
                                    {transfer.created_by?.name ?? '-'}
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 rounded-md border bg-muted/20 p-4 md:grid-cols-[1fr_auto_1fr]">
                            <div className="space-y-1">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">Desde</p>
                                <p className="flex items-center gap-2 font-medium text-foreground">
                                    <Building2 className="h-4 w-4 text-muted-foreground" />
                                    {transfer.from_workspace?.name ?? '-'}
                                </p>
                            </div>

                            <div className="flex items-center justify-center">
                                <ArrowLeftRight className="h-5 w-5 text-muted-foreground" />
                            </div>

                            <div className="space-y-1">
                                <p className="text-xs tracking-wide text-muted-foreground uppercase">Hacia</p>
                                <p className="flex items-center gap-2 font-medium text-foreground">
                                    <Building2 className="h-4 w-4 text-muted-foreground" />
                                    {transfer.to_workspace?.name ?? '-'}
                                </p>
                            </div>
                        </div>

                        <div className="space-y-2 border-b pb-6">
                            <p className="text-sm text-muted-foreground">Observaciones</p>
                            <p className="text-base text-foreground">{transferNote || '-'}</p>
                        </div>

                        <div className="rounded-md border p-4">
                            <p className="mb-2 text-sm text-muted-foreground">Cantidad transferida</p>
                            <p className={`text-4xl font-semibold tracking-tight ${isIncoming ? 'text-emerald-600' : 'text-red-600'}`}>
                                {isIncoming ? '+' : '-'}
                                {formattedQuantity}
                            </p>
                        </div>

                        <Separator />

                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/inventory-adjustments/create">Crear ajuste</Link>
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={`/products/${transfer.product_id}`}>
                                    <Package className="mr-2 h-4 w-4" />
                                    Ver producto
                                </Link>
                            </Button>
                            <Button type="button" className="bg-yellow-600 text-white hover:bg-yellow-700" asChild>
                                <Link href="/stock-transfers/create">Nueva transferencia</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
