import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';

import { PaymentRegistrationModal } from '@/components/payment-registration-modal';
import { Button } from '@/components/ui/button';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BankAccount, type BreadcrumbItem, type Invoice } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturas',
        href: '/invoices',
    },
];

interface Props {
    invoices: TableResource<Invoice>;
    bankAccounts?: BankAccount[];
    paymentMethods?: Record<string, string>;
}

export default function InvoicesIndex({ invoices, bankAccounts = [], paymentMethods = {} }: Props) {
    const { can } = usePermissions();
    const [paymentModalOpen, setPaymentModalOpen] = useState(false);
    const [selectedInvoice, setSelectedInvoice] = useState<Invoice | null>(null);

    const handleOpenPaymentModal = (invoice: Invoice) => {
        setSelectedInvoice(invoice);
        setPaymentModalOpen(true);

        if (!bankAccounts.length || !Object.keys(paymentMethods).length) {
            router.reload({ only: ['bankAccounts', 'paymentMethods'] });
        }
    };

    const handleClosePaymentModal = () => {
        setPaymentModalOpen(false);
        setSelectedInvoice(null);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturas" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Facturas</h1>
                        <p className="text-gray-600 dark:text-gray-400">Gestiona tus facturas y realiza un seguimiento de los pagos.</p>
                    </div>

                    {can('create invoices') && (
                        <Button asChild className="bg-primary hover:bg-primary/90">
                            <Link href="/invoices/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva factura
                            </Link>
                        </Button>
                    )}
                </div>

                <DataTable<Invoice>
                    resource={invoices}
                    baseUrl="/invoices"
                    getRowKey={(invoice) => invoice.id}
                    emptyMessage="No se encontraron facturas"
                    emptyState={
                        can('create invoices') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron facturas</div>
                                <Button asChild>
                                    <Link href="/invoices/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Crear factura
                                    </Link>
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{
                        openPaymentModal: handleOpenPaymentModal,
                    }}
                />
            </div>

            {selectedInvoice && (
                <PaymentRegistrationModal
                    isOpen={paymentModalOpen}
                    onClose={handleClosePaymentModal}
                    invoice={selectedInvoice}
                    bankAccounts={bankAccounts}
                    paymentMethods={paymentMethods}
                />
            )}
        </AppLayout>
    );
}
