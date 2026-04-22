import { Head, router, useForm } from '@inertiajs/react';

import ExpenseForm, { type ExpenseFormData, type SupplierSearchResult } from '@/components/expenses/expense-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Expense, type Workspace } from '@/types';

interface Props {
    expense: Expense;
    statuses: Record<string, string>;
    currentWorkspace?: Workspace | null;
    availableWorkspaces: Workspace[];
    supplierSearchResults?: SupplierSearchResult[];
    initialSupplier?: SupplierSearchResult | null;
}

export default function ExpensesEdit({
    expense,
    statuses,
    currentWorkspace,
    availableWorkspaces,
    supplierSearchResults = [],
    initialSupplier = null,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Gastos',
            href: '/expenses',
        },
        {
            title: expense.document_number,
            href: `/expenses/${expense.id}`,
        },
        {
            title: 'Editar',
            href: `/expenses/${expense.id}/edit`,
        },
    ];

    const { data, setData, processing, errors } = useForm<ExpenseFormData>({
        workspace_id: expense.workspace_id,
        contact_id: expense.contact_id,
        document_number: expense.document_number,
        issue_date: expense.issue_date,
        subtotal_amount: String(expense.subtotal_amount ?? '0.00'),
        itbis_amount: String(expense.itbis_amount ?? '0.00'),
        isc_amount: String(expense.isc_amount ?? '0.00'),
        withheld_itbis_amount: String(expense.withheld_itbis_amount ?? '0.00'),
        withheld_isr_amount: String(expense.withheld_isr_amount ?? '0.00'),
        is_informal: expense.is_informal,
        status: expense.status,
        notes: expense.notes ?? '',
        attachments: [],
        remove_attachment_ids: [],
    });

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        router.post(
            `/expenses/${expense.id}`,
            {
                _method: 'put',
                ...data,
            },
            {
                forceFormData: true,
                onSuccess: () => router.visit(`/expenses/${expense.id}`),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${expense.document_number}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <ExpenseForm
                    mode="edit"
                    expenseId={expense.id}
                    data={data}
                    setData={setData}
                    errors={errors as Record<string, string>}
                    processing={processing}
                    onSubmit={handleSubmit}
                    statuses={statuses}
                    currentWorkspace={currentWorkspace}
                    availableWorkspaces={availableWorkspaces}
                    supplierSearchResults={supplierSearchResults}
                    initialSupplier={initialSupplier}
                    existingAttachments={expense.media ?? []}
                />
            </div>
        </AppLayout>
    );
}
