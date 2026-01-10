import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';
import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BankAccount, type BreadcrumbItem, type Quotation } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cotizaciones',
        href: '/quotations',
    },
];

interface Props {
    quotations: TableResource<Quotation>;
    bankAccounts?: BankAccount[];
    paymentMethods?: Record<string, string>;
}

export default function QuotationsIndex({ quotations, bankAccounts = [], paymentMethods = {} }: Props) {
    const { can } = usePermissions();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizaciones" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Cotizaciones</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona tus cotizaciones y realiza un seguimiento de los pagos.</p>
                    </div>

                    {can('create quotations') && (
                        <Button asChild className="bg-primary hover:bg-primary/90">
                            <Link href="/quotations/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva cotización
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<Quotation>
                    resource={quotations}
                    baseUrl="/quotations"
                    getRowKey={(quotation) => quotation.id}
                    emptyMessage="No se encontraron cotizaciones"
                    emptyState={
                        can('create quotations') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron cotizaciones</div>
                                <Button asChild>
                                    <Link href="/quotations/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear cotización
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
