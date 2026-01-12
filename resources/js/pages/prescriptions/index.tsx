import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';
import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { Prescription, type BankAccount, type BreadcrumbItem, type Quotation } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recetas',
        href: '/prescriptions',
    },
];

interface Props {
    prescriptions: TableResource<Prescription>;
    bankAccounts?: BankAccount[];
    paymentMethods?: Record<string, string>;
}

export default function PrescriptionsIndex({ prescriptions}: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recetas" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Recetas</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona las recetas de tus pacientes.</p>
                    </div>

                    {can('create prescriptions') && (
                        <Button asChild className="bg-primary hover:bg-primary/90">
                            <Link href="/prescriptions/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva receta
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<Prescription>
                    resource={prescriptions}
                    baseUrl="/prescriptions"
                    getRowKey={(prescription) => prescription.id}
                    emptyMessage="No se encontraron recetas"
                    emptyState={
                        can('create prescriptions') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron recetas</div>
                                <Button asChild>
                                    <Link href="/prescriptions/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear receta
                                    </Link>
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{
                    }}
                />
            </div>
        </AppLayout>
    );
}
