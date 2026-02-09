import { Head, Link } from '@inertiajs/react';
import { ArrowLeftRight, Plus } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Transferencias de inventario',
        href: '/stock-transfers',
    },
];

interface StockTransferRow {
    id: number;
    from_workspace_id: number | null;
    to_workspace_id: number | null;
    quantity: number;
    direction?: string;
    counterpart_workspace?: string;
}

interface Props {
    transfers: TableResource<StockTransferRow>;
    current_workspace_id: number;
}

export default function StockTransfersIndex({ transfers, current_workspace_id }: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transferencias de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Transferencias de inventario</h1>
                        <p className="text-gray-600 dark:text-gray-400">Consulta entradas y salidas entre almacenes con busqueda rapida.</p>
                    </div>

                    {can('transfer inventory') && (
                        <Button asChild>
                            <Link href="/stock-transfers/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva transferencia
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<StockTransferRow>
                    resource={transfers}
                    baseUrl="/stock-transfers"
                    getRowKey={(transfer) => transfer.id}
                    emptyMessage="No se encontraron transferencias de inventario"
                    emptyState={
                        can('transfer inventory') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 flex items-center justify-center gap-2 text-muted-foreground">
                                    <ArrowLeftRight className="h-4 w-4" />
                                    No se encontraron transferencias
                                </div>
                                <Button asChild>
                                    <Link href="/stock-transfers/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear transferencia
                                    </Link>
                                </Button>
                            </div>
                        ) : undefined
                    }
                    renderCell={(key, value, row) => {
                        if (key === 'direction') {
                            return row.from_workspace_id === current_workspace_id ? (
                                <Badge variant="destructive">Saliente</Badge>
                            ) : (
                                <Badge variant="default">Entrante</Badge>
                            );
                        }

                        if (key === 'quantity') {
                            const isIncoming = row.to_workspace_id === current_workspace_id;
                            const amount = Number(value ?? 0);

                            return (
                                <span className={`font-semibold ${isIncoming ? 'text-emerald-600' : 'text-red-600'}`}>
                                    {isIncoming ? '+' : '-'}
                                    {new Intl.NumberFormat('es-DO', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(amount)}
                                </span>
                            );
                        }

                        return undefined;
                    }}
                />
            </div>
        </AppLayout>
    );
}
