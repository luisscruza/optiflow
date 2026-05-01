import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Expense } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gastos',
        href: '/expenses',
    },
];

interface Props {
    expenses: TableResource<Expense>;
}

export default function ExpensesIndex({ expenses }: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gastos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Gastos</h1>
                        <p className="text-gray-600 dark:text-gray-400">Registra y consulta las facturas de suplidores por sucursal.</p>
                    </div>

                    {can('create expenses') && (
                        <Button asChild>
                            <Link href="/expenses/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo gasto
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<Expense>
                    resource={expenses}
                    baseUrl="/expenses"
                    getRowKey={(expense) => expense.id}
                    emptyMessage="No se encontraron gastos"
                    emptyState={
                        can('create expenses') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron gastos</div>
                                <Button asChild>
                                    <Link href="/expenses/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Registrar gasto
                                    </Link>
                                </Button>
                            </div>
                        ) : undefined
                    }
                />
            </div>
        </AppLayout>
    );
}
