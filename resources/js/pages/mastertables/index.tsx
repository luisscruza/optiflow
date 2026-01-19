import { Head, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';

import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Mastertable } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Tablas maestras',
        href: '/mastertables',
    },
];

interface Props {
    mastertables: TableResource<Mastertable>;
}

export default function MastertablesIndex({ mastertables }: Props) {
    const { can } = usePermissions();

    const handleCreate = () => {
        router.visit('/mastertables/create');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tablas maestras" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Tablas maestras</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Gestiona las tablas maestras y sus elementos para personalizar el sistema según tus necesidades.
                        </p>
                    </div>

                    {can('create mastertables') && (
                        <Button onClick={handleCreate}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva tabla maestra
                        </Button>
                    )}
                </div>

                <DataTable<Mastertable>
                    resource={mastertables}
                    baseUrl="/mastertables"
                    getRowKey={(mastertable) => mastertable.id}
                    emptyMessage="No se encontraron tablas maestras"
                    emptyState={
                        can('create mastertables') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron tablas maestras</div>
                                <Button onClick={handleCreate}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear primera tabla maestra
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{}}
                />
            </div>
        </AppLayout>
    );
}
