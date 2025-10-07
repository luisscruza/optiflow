import { Head, usePage } from '@inertiajs/react';

import PrescriptionForm from '@/components/prescriptions/prescription-form';
import AppLayout from '@/layouts/app-layout';
import { MasterTableData, type BreadcrumbItem, type Contact, type Workspace } from '@/types';
import prescriptions from '@/routes/prescriptions';

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
    customers: Contact[];
    optometrists: Contact[];
    masterTables: Record<string, MasterTableData>;
}

export default function CreatePrescription({ customers, optometrists, masterTables }: Props) {
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null; available: Workspace[] } };

    if (!workspace || !workspace.available?.length) {
        return null; 
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva receta" />

            <div className="min-h-screen bg-gray-50/30">
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <PrescriptionForm
                        customers={customers}
                        optometrists={optometrists}
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
