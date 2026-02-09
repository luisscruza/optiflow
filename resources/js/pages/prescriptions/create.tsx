import { Head, usePage } from '@inertiajs/react';

import PrescriptionForm from '@/components/prescriptions/prescription-form';
import AppLayout from '@/layouts/app-layout';
import prescriptions from '@/routes/prescriptions';
import { MasterTableData, type BreadcrumbItem, type Contact, type Workspace } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recetas',
        href: prescriptions.index().url,
    },
    {
        title: 'Nueva receta',
        href: prescriptions.create().url,
    },
];

interface Props {
    initialContact?: Contact | null;
    customerSearchResults?: Contact[];
    initialOptometrist?: Contact | null;
    optometristSearchResults?: Contact[];
    masterTables: Record<string, MasterTableData>;
}

export default function CreatePrescription({
    initialContact,
    customerSearchResults,
    initialOptometrist,
    optometristSearchResults,
    masterTables,
}: Props) {
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null; available: Workspace[] } };

    if (!workspace || !workspace.available?.length) {
        return null;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva receta" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <PrescriptionForm
                        initialContact={initialContact}
                        customerSearchResults={customerSearchResults}
                        initialOptometrist={initialOptometrist}
                        optometristSearchResults={optometristSearchResults}
                        masterTables={masterTables}
                        workspace={workspace}
                        submitUrl={prescriptions.store().url}
                        redirectUrl={prescriptions.index().url}
                        submitButtonText="Crear receta"
                        isEditing={false}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
