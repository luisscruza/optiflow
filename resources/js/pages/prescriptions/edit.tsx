import { Head, usePage } from '@inertiajs/react';

import PrescriptionForm from '@/components/prescriptions/prescription-form';
import AppLayout from '@/layouts/app-layout';
import { MasterTableData, type BreadcrumbItem, type Contact, type Workspace } from '@/types';
import prescriptions from '@/routes/prescriptions';

interface Prescription {
    id: number;
    workspace_id: number;
    contact_id: number;
    optometrist_id: number;
    motivos_consulta: number[];
    estado_salud_actual: number[];
    historia_ocular_familiar: number[];
    // Add other prescription fields as needed
}

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
            title: `Editar receta #${prescription.id}`,
            href: `/prescriptions/${prescription.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar receta #${prescription.id}`} />

            <div className="min-h-screen bg-gray-50/30">
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <PrescriptionForm
                        customers={customers}
                        optometrists={optometrists}
                        masterTables={masterTables}
                        workspace={workspace}
                        initialData={{
                            workspace_id: prescription.workspace_id,
                            contact_id: prescription.contact_id,
                            optometrist_id: prescription.optometrist_id,
                            motivos_consulta: prescription.motivos_consulta,
                            estado_salud_actual: prescription.estado_salud_actual,
                            historia_ocular_familiar: prescription.historia_ocular_familiar,
                        }}
                        submitUrl={`/prescriptions/${prescription.id}`}
                        redirectUrl={`/prescriptions/${prescription.id}`}
                        submitButtonText="Actualizar receta"
                        isEditing={true}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
