import { Head } from '@inertiajs/react';
import { Warehouse } from 'lucide-react';

import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Stock consolidado',
        href: '/inventory/consolidated-stock',
    },
];

interface ConsolidatedStockRow {
    id: number;
    name: string;
    sku: string | null;
    total_quantity: number;
}

interface Props {
    stock: TableResource<ConsolidatedStockRow>;
}

export default function ConsolidatedStockIndex({ stock }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stock consolidado" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stock consolidado</h1>
                    <p className="text-gray-600 dark:text-gray-400">Existencias de cada producto en todas las sucursales.</p>
                </div>

                <DataTable<ConsolidatedStockRow>
                    resource={stock}
                    baseUrl="/inventory/consolidated-stock"
                    getRowKey={(product) => product.id}
                    emptyMessage="No hay productos con control de inventario"
                    emptyState={
                        <div className="flex items-center justify-center gap-2 py-8 text-muted-foreground">
                            <Warehouse className="h-4 w-4" />
                            No hay productos con control de inventario
                        </div>
                    }
                />
            </div>
        </AppLayout>
    );
}
