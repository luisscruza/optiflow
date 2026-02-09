import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

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
        title: 'Ajustes de inventario',
        href: '/inventory-adjustments',
    },
];

interface InventoryAdjustmentRow {
    id: number;
    adjustment_date: string;
    workspace_id: number;
    total_adjusted: number | string;
    notes?: string | null;
}

interface Props {
    adjustments: TableResource<InventoryAdjustmentRow>;
}

export default function InventoryAdjustmentsIndex({ adjustments }: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajustes de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Ajustes de inventario</h1>
                        <p className="text-gray-600 dark:text-gray-400">Consulta y filtra los ajustes por sucursal o almacen.</p>
                    </div>

                    {can('adjust inventory') && (
                        <Button asChild>
                            <Link href="/inventory-adjustments/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo ajuste
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<InventoryAdjustmentRow>
                    resource={adjustments}
                    baseUrl="/inventory-adjustments"
                    getRowKey={(adjustment) => adjustment.id}
                    emptyMessage="No se encontraron ajustes de inventario"
                />
            </div>
        </AppLayout>
    );
}
