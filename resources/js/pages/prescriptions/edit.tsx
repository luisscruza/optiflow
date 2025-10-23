import { Head, usePage } from '@inertiajs/react';

import PrescriptionForm from '@/components/prescriptions/prescription-form';
import AppLayout from '@/layouts/app-layout';
import { MasterTableData, Prescription, type BreadcrumbItem, type Contact, type Workspace } from '@/types';
import prescriptions from '@/routes/prescriptions';

interface Props {
    prescription: Prescription;
    customers: Contact[];
    optometrists: Contact[];
    masterTables: Record<string, MasterTableData>;
}

export default function EditPrescription({ prescription, customers, optometrists, masterTables }: Props) {
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null; available: Workspace[] } };

    if (!workspace || !workspace.available?.length) {
        return null; 
    }

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Recetas',
            href: prescriptions.index().url,
        },
        {
            title: `Receta #${prescription.id}`,
            href: prescriptions.show(prescription).url,
        },
        {
            title: 'Editar',
            href: prescriptions.edit(prescription).url,
        },
    ];

    // Convert prescription data to match the form's expected format
    const initialData = {
        workspace_id: prescription.workspace?.id || null,
        contact_id: prescription.patient?.id || null,
        optometrist_id: prescription.optometrist?.id || null,
        // Spread all prescription properties to include all form fields
        ...prescription,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Receta #${prescription.id}`} />

            <div className="min-h-screen bg-gray-50/30">
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <PrescriptionForm
                        customers={customers}
                        optometrists={optometrists}
                        masterTables={masterTables}
                        workspace={workspace}
                        initialData={initialData}
                        submitUrl={prescriptions.update(prescription).url}
                        redirectUrl={prescriptions.show(prescription).url}
                        submitButtonText="Actualizar receta"
                        isEditing={true}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
