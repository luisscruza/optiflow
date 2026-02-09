import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

import InvoiceForm, {
    type ContactSearchResult,
    type DocumentSubtype,
    type InvoiceFormData,
    type InvoiceItem,
} from '@/components/invoices/invoice-form';
import AppLayout from '@/layouts/app-layout';
import { type BankAccount, type BreadcrumbItem, type Contact, type Product, type Salesman, type TaxesGroupedByType, type Workspace } from '@/types';

interface FromQuotation {
    id: number;
    document_number: string;
    contact_id: number;
    contact: Contact;
    issue_date: string;
    due_date?: string;
    payment_term: string;
    notes: string;
    items: InvoiceItem[];
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
}

interface Props {
    documentSubtypes: DocumentSubtype[];
    initialContact?: ContactSearchResult | null;
    customerSearchResults?: ContactSearchResult[];
    products: Product[];
    productSearchResults?: Product[];
    ncf?: string | null;
    document_subtype_id?: number | null;
    currentWorkspace?: Workspace | null;
    availableWorkspaces?: Workspace[];
    defaultNote?: string;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
    fromQuotation?: FromQuotation | null;
    taxesGroupedByType: TaxesGroupedByType;
    salesmen: Salesman[];
}

const createDefaultItems = (): InvoiceItem[] => [
    {
        id: '1',
        product_id: null,
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_rate: 0,
        discount_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        total: 0,
        taxes: [],
    },
    {
        id: '2',
        product_id: null,
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_rate: 0,
        discount_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        total: 0,
        taxes: [],
    },
    {
        id: '3',
        product_id: null,
        description: '',
        quantity: 1,
        unit_price: 0,
        discount_rate: 0,
        discount_amount: 0,
        tax_rate: 0,
        tax_amount: 0,
        total: 0,
        taxes: [],
    },
];

export default function CreateInvoice({
    documentSubtypes,
    initialContact,
    customerSearchResults,
    products,
    productSearchResults,
    ncf,
    document_subtype_id,
    currentWorkspace,
    availableWorkspaces,
    defaultNote,
    bankAccounts,
    paymentMethods,
    taxesGroupedByType,
    salesmen,
    fromQuotation,
}: Props) {
    const defaultItems: InvoiceItem[] = fromQuotation?.items ?? createDefaultItems();

    const { data, setData, post, processing, errors } = useForm<InvoiceFormData>({
        document_subtype_id: document_subtype_id || null,
        contact_id: fromQuotation?.contact_id ?? initialContact?.id ?? null,
        workspace_id: currentWorkspace?.id || null,
        issue_date: fromQuotation?.issue_date ?? new Date().toISOString().split('T')[0],
        due_date: fromQuotation?.due_date ?? new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // Default to 7 days from now
        payment_term: fromQuotation?.payment_term ?? 'manual',
        notes: fromQuotation?.notes ?? defaultNote ?? '',
        ncf: ncf || '',
        items: defaultItems,
        subtotal: fromQuotation?.subtotal ?? 0,
        discount_total: fromQuotation?.discount_total ?? 0,
        tax_amount: fromQuotation?.tax_amount ?? 0,
        total: fromQuotation?.total ?? 0,
        register_payment: false,
        payment_bank_account_id: null,
        payment_amount: 0,
        payment_method: '',
        payment_notes: '',
        salesmen_ids: [],
        quotation_id: fromQuotation?.id ?? null,
    });

    // Sync NCF prop changes to form data
    useEffect(() => {
        if (ncf && ncf !== data.ncf) {
            setData('ncf', ncf);
        }
    }, [ncf]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/invoices', {
            onSuccess: () => router.visit('/invoices'),
        });
    };

    const breadcrumbs: BreadcrumbItem[] = fromQuotation
        ? [
              { title: 'Cotizaciones', href: '/quotations' },
              { title: `Cotización #${fromQuotation.document_number}`, href: `/quotations/${fromQuotation.id}` },
              { title: 'Convertir a factura', href: '#' },
          ]
        : [
              { title: 'Facturas', href: '/invoices' },
              { title: 'Nueva factura', href: '/invoices/create' },
          ];

    const pageTitle = fromQuotation ? `Convertir cotización #${fromQuotation.document_number} a factura` : 'Nueva factura';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <InvoiceForm
                    mode="create"
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={handleSubmit}
                    documentSubtypes={documentSubtypes}
                    initialContact={fromQuotation?.contact ?? initialContact ?? null}
                    customerSearchResults={customerSearchResults}
                    products={products}
                    productSearchResults={productSearchResults}
                    ncf={ncf}
                    currentWorkspace={currentWorkspace}
                    availableWorkspaces={availableWorkspaces}
                    bankAccounts={bankAccounts}
                    paymentMethods={paymentMethods}
                    taxesGroupedByType={taxesGroupedByType}
                    salesmen={salesmen}
                />
            </div>
        </AppLayout>
    );
}
