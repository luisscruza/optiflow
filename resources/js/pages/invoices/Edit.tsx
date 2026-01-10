import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import InvoiceForm, { type DocumentSubtype, type InvoiceFormData, type InvoiceItem } from '@/components/invoices/invoice-form';
import { type SelectedTax } from '@/components/taxes/tax-multi-select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Product, type Salesman, type TaxesGroupedByType, type Workspace } from '@/types';

interface Invoice {
    id: number;
    document_number: string;
    ncf: string;
    contact_id: number;
    workspace_id: number;
    document_subtype_id: number;
    issue_date: string;
    due_date: string;
    payment_term: string;
    notes: string;
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
    status: string;
    contact: Contact;
    document_subtype: DocumentSubtype;
    workspace: Workspace;
    salesmen?: Salesman[];
    items: Array<{
        id: number;
        product_id: number | null;
        description: string;
        quantity: number;
        unit_price: number;
        discount_rate: number;
        discount_amount: number;
        tax_rate: number;
        tax_amount: number;
        total: number;
        product?: Product;
        taxes?: Array<{
            id: number;
            name: string;
            type: string;
            pivot: { rate: number; amount: number };
        }>;
    }>;
}

interface Props {
    invoice: Invoice;
    documentSubtypes: DocumentSubtype[];
    customers: Contact[];
    products: Product[];
    currentWorkspace?: Workspace | null;
    availableWorkspaces?: Workspace[];
    ncf?: string | null;
    taxesGroupedByType: TaxesGroupedByType;
    salesmen: Salesman[];
}

// Helper function to format date for HTML date input
const formatDateForInput = (dateString: string): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    return date.toISOString().split('T')[0];
};

// Convert invoice items to the format expected by the form
const convertInvoiceItems = (items: Invoice['items']): InvoiceItem[] => {
    return items.map((item, index) => ({
        id: (index + 1).toString(),
        product_id: item.product_id,
        description: item.description || '',
        quantity: item.quantity || 1,
        unit_price: item.unit_price || 0,
        discount_rate: item.discount_rate || 0,
        discount_amount: item.discount_amount || 0,
        tax_rate: item.tax_rate || 0,
        taxes: item.taxes
            ? item.taxes.map((tax) => ({
                  id: tax.id,
                  name: tax.name,
                  type: tax.type,
                  rate: tax.pivot.rate,
                  amount: tax.pivot.amount,
              }))
            : [],
        tax_amount: item.tax_amount || 0,
        total: item.total || 0,
    }));
};

export default function EditInvoice({
    invoice,
    documentSubtypes,
    customers,
    products,
    currentWorkspace,
    availableWorkspaces,
    ncf,
    salesmen,
    taxesGroupedByType,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Facturas', href: '/invoices' },
        { title: 'Editar factura', href: '#' },
    ];

    const { data, setData, put, processing, errors } = useForm<InvoiceFormData>({
        document_subtype_id: invoice.document_subtype_id,
        contact_id: invoice.contact_id,
        workspace_id: invoice.workspace_id,
        issue_date: formatDateForInput(invoice.issue_date),
        due_date: formatDateForInput(invoice.due_date),
        payment_term: invoice.payment_term,
        notes: invoice.notes || '',
        ncf: ncf || invoice.ncf || '',
        items: convertInvoiceItems(invoice.items),
        subtotal: invoice.subtotal,
        discount_total: invoice.discount_total,
        tax_amount: invoice.tax_amount,
        total: invoice.total,
        salesmen_ids: invoice.salesmen?.map((s) => s.id) ?? [],
        // Payment fields not used in edit mode but required by type
        register_payment: false,
        payment_bank_account_id: null,
        payment_amount: 0,
        payment_method: '',
        payment_notes: '',
        quotation_id: null,
    });

    // Sync NCF prop changes to form data
    useEffect(() => {
        if (ncf && ncf !== data.ncf) {
            setData('ncf', ncf);
        }
    }, [ncf]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/invoices/${invoice.id}`, {
            onSuccess: () => router.visit('/invoices'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar factura ${invoice.document_number}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <InvoiceForm
                    mode="edit"
                    invoiceId={invoice.id}
                    invoiceNumber={invoice.document_number}
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={handleSubmit}
                    documentSubtypes={documentSubtypes}
                    customers={customers}
                    products={products}
                    ncf={ncf}
                    currentWorkspace={currentWorkspace}
                    availableWorkspaces={availableWorkspaces}
                    taxesGroupedByType={taxesGroupedByType}
                    salesmen={salesmen}
                />
            </div>
        </AppLayout>
    );
}
