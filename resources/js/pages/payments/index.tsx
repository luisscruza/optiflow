import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';

import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Payment } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pagos recibidos',
        href: '/payments',
    },
];

interface Props {
    payments: TableResource<Payment>;
}

export default function PaymentsIndex({ payments }: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pagos recibidos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Pagos recibidos</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona todos los pagos recibidos.</p>
                    </div>

                    <div className="flex items-center gap-2">
                        {can('create payments') && (
                            <Button asChild className="bg-primary hover:bg-primary/90">
                                <Link href="/payments/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Registrar pago
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <DataTable<Payment>
                    resource={payments}
                    baseUrl="/payments"
                    getRowKey={(payment) => payment.id}
                    emptyMessage="No se encontraron pagos"
                    emptyState={
                        can('create payments') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron pagos</div>
                                <Button asChild>
                                    <Link href="/payments/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Registrar pago
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
