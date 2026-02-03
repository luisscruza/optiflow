import { Head, router } from '@inertiajs/react';
import { Plus, Upload } from 'lucide-react';
import { useState } from 'react';

import { create as contactImportCreate } from '@/actions/App/Http/Controllers/ContactImportController';
import { usePermissions } from '@/hooks/use-permissions';

import QuickContactModal from '@/components/contacts/quick-contact-modal';
import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { Contact, type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contactos',
        href: '/contacts',
    },
];

interface Props {
    contacts: TableResource<Contact>;
}

export default function ContactsIndex({ contacts }: Props) {
    const { can } = usePermissions();
    const [showQuickModal, setShowQuickModal] = useState(false);

    const handleAdvancedForm = () => {
        router.visit('/contacts/create');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Contactos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Contactos</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Crea tus clientes, proveedores y demás contactos para asociarlos en tus documentos.
                        </p>
                    </div>

                    {can('create contacts') && (
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                onClick={() => router.visit(contactImportCreate().url)}
                                className="border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900/20 dark:text-gray-300"
                            >
                                <Upload className="mr-2 h-4 w-4" />
                                Importar contactos
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setShowQuickModal(true)}
                                className="border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900/20 dark:text-gray-300"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo contacto
                            </Button>
                        </div>
                    )}
                </div>

                <DataTable<Contact>
                    resource={contacts}
                    baseUrl="/contacts"
                    getRowKey={(contact) => contact.id}
                    emptyMessage="No se encontraron contactos"
                    emptyState={
                        can('create contacts') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron contactos</div>
                                <Button onClick={() => setShowQuickModal(true)}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear primer contacto
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{}}
                />

                {/* Quick Contact Modal */}
                <QuickContactModal
                    open={showQuickModal}
                    types={['customer', 'optometrist', 'supplier']}
                    onOpenChange={setShowQuickModal}
                    onAdvancedForm={handleAdvancedForm}
                />
            </div>
        </AppLayout>
    );
}
