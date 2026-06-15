import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ReceivedDocumentSummary } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, Download, RefreshCw } from 'lucide-react';

interface Props {
    documents: TableResource<ReceivedDocumentSummary>;
    summary: {
        subtotal: number;
        tax_amount: number;
        total_amount: number;
    };
    error: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturación electrónica',
        href: '/settings/electronic-invoicing',
    },
    {
        title: 'Recepción',
        href: '/electronic-invoicing/received',
    },
];

const formatAmount = (amount: number, currency: string): string => {
    return new Intl.NumberFormat('es-DO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
        .format(amount)
        .concat(` ${currency}`);
};

const formatType = (document: ReceivedDocumentSummary): string => {
    return document.is_credit_note ? `E${document.ecf_type} · Nota de crédito` : document.ecf_type;
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleString('es-DO');
};

export default function ElectronicInvoicingReceivedIndex({ documents, summary, error }: Props) {
    const reloadDocuments = () => {
        window.location.reload();
    };

    const exportDocuments = () => {
        window.location.href = `/electronic-invoicing/received/export${window.location.search}`;
    };

    const currentCurrency = (documents.data.data[0]?.currency as string | undefined) || 'DOP';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recepción de documentos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Recepción</h1>
                        <p className="text-gray-600 dark:text-gray-400">Consulta de documentos recibidos desde la Sede Electrónica.</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button type="button" variant="outline" onClick={exportDocuments}>
                            <Download className="mr-2 h-4 w-4" />
                            Exportar CSV
                        </Button>
                        <Button type="button" variant="outline" onClick={reloadDocuments}>
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Actualizar
                        </Button>
                    </div>
                </div>

                {error && (
                    <Alert variant="destructive" className="mb-6">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>No se pudieron cargar los documentos</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                )}

                <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Subtotal (rango)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold">{formatAmount(summary.subtotal, currentCurrency)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">ITBIS (rango)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold">{formatAmount(summary.tax_amount, currentCurrency)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Total con ITBIS (rango)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold">{formatAmount(summary.total_amount, currentCurrency)}</p>
                        </CardContent>
                    </Card>
                </div>

                <DataTable<ReceivedDocumentSummary>
                    resource={documents}
                    baseUrl="/electronic-invoicing/received"
                    emptyMessage="No se encontraron documentos recibidos"
                    renderCell={(key, value, row) => {
                        if (key === 'received_at') {
                            return formatDate(value as string | null);
                        }

                        if (key === 'encf') {
                            return (
                                <div>
                                    <div className="font-medium text-gray-900">{String(value ?? '-')}</div>
                                    {row.reference?.modified_encf && (
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            Modifica:{' '}
                                            {row.modified_document?.id ? (
                                                <Link href={`/electronic-invoicing/received/${row.modified_document.id}`} className="font-medium text-primary hover:underline">
                                                    {row.reference.modified_encf}
                                                </Link>
                                            ) : (
                                                <span>{row.reference.modified_encf}</span>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        }

                        if (key === 'ecf_type') {
                            return formatType(row);
                        }

                        if (key === 'total_amount') {
                            return formatAmount(Number(value ?? 0), row.currency || 'DOP');
                        }

                        return undefined;
                    }}
                />
            </div>
        </AppLayout>
    );
}
