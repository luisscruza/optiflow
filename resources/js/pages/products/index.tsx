import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Package, Plus } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { Product, type BreadcrumbItem } from '@/types';
import { create, index } from '@/routes/products';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Productos',
        href: index().url,
    },
];

interface Props {
    products: TableResource<Product>;
}

export default function ProductsIndex({ products }: Props) {
    const { can } = usePermissions();
    const { format: formatCurrency } = useCurrency();

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

                <DataTable<Product>
                    resource={products}
                    baseUrl={index().url}
                    getRowKey={(product) => product.id}
                    emptyMessage="No se encontraron productos"
                    emptyState={
                        can('create products') ? (
                            <div className="py-8 text-center">
                                <Package className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                    No se encontraron productos
                                </h3>
                                <p className="mb-6 text-gray-600 dark:text-gray-400">Comienza agregando tu primer producto al catálogo.</p>
                                <Button asChild>
                                    <Link prefetch href={create().url}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Agregar producto
                                    </Link>
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{}}
                    renderCell={(key, value, row) => {
                        if (key === 'inventory') {
                            const inventoryData = value as { quantity: number; isLow: boolean } | string;

                            if (typeof inventoryData === 'string') {
                                if (inventoryData === 'Sin seguimiento') {
                                    return <Badge variant="outline">Sin seguimiento</Badge>;
                                }
                                if (inventoryData === 'Sin datos') {
                                    return <Badge variant="secondary">Sin datos de inventario</Badge>;
                                }
                                return inventoryData;
                            }

                            return (
                                <div className="flex items-center gap-2">
                                    <span className="font-medium">{inventoryData.quantity}</span>
                                    {inventoryData.isLow && (
                                        <Badge variant="destructive" className="text-xs">
                                            <AlertTriangle className="mr-1 h-3 w-3" />
                                            Bajo
                                        </Badge>
                                    )}
                                </div>
                            );
                        }

                        if (key === 'cost') {
                            if (value === '-') return value;
                            return formatCurrency(Number(value));
                        }

                        return undefined;
                    }}
                />
            </div>
        </AppLayout>
    );
}
