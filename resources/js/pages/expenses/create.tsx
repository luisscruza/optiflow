import { Head, router, useForm } from '@inertiajs/react';

import ExpenseForm, { type ExpenseFormData, type SupplierSearchResult } from '@/components/expenses/expense-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Workspace } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gastos',
        href: '/expenses',
    },
    {
        title: 'Nuevo gasto',
        href: '/expenses/create',
    },
];

interface Props {
    statuses: Record<string, string>;
    currentWorkspace?: Workspace | null;
    availableWorkspaces: Workspace[];
    supplierSearchResults?: SupplierSearchResult[];
}

export default function ExpensesCreate({ statuses, currentWorkspace, availableWorkspaces, supplierSearchResults = [] }: Props) {
    const { data, setData, post, processing, errors } = useForm<ExpenseFormData>({
        workspace_id: currentWorkspace?.id ?? availableWorkspaces[0]?.id ?? null,
        contact_id: null,
        document_number: '',
        issue_date: new Date().toISOString().split('T')[0],
        subtotal_amount: '0.00',
        itbis_amount: '0.00',
        isc_amount: '0.00',
        withheld_itbis_amount: '0.00',
        withheld_isr_amount: '0.00',
        is_informal: false,
        status: 'pending',
        notes: '',
        attachments: [],
        remove_attachment_ids: [],
    });

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        post('/expenses', {
            forceFormData: data.attachments.length > 0,
            onSuccess: () => router.visit('/expenses'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo gasto" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <ExpenseForm
                    mode="create"
                    data={data}
                    setData={setData}
                    errors={errors as Record<string, string>}
                    processing={processing}
                    onSubmit={handleSubmit}
                    statuses={statuses}
                    currentWorkspace={currentWorkspace}
                    availableWorkspaces={availableWorkspaces}
                    supplierSearchResults={supplierSearchResults}
                />
            </div>
        </AppLayout>
    );
}
