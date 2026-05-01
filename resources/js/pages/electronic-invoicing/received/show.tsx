import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ReceivedDocumentDetail } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, Printer, Receipt } from 'lucide-react';

interface Props {
    document: ReceivedDocumentDetail | null;
    canConvertToExpense: boolean;
}

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

const formatAmount = (amount: number, currency: string): string => {
    return new Intl.NumberFormat('es-DO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
        .format(amount)
        .concat(` ${currency}`);
};

const statusLabelMap: Record<string, string> = {
    received: 'Recibido',
    processed: 'Procesado',
    accepted: 'Aceptado',
    rejected: 'Rechazado',
};

const statusClassMap: Record<string, string> = {
    received: 'bg-blue-100 text-blue-700 hover:bg-blue-100',
    processed: 'bg-amber-100 text-amber-700 hover:bg-amber-100',
    accepted: 'bg-emerald-100 text-emerald-700 hover:bg-emerald-100',
    rejected: 'bg-red-100 text-red-700 hover:bg-red-100',
};

export default function ElectronicInvoicingReceivedShow({ document, canConvertToExpense }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturación electrónica',
            href: '/settings/electronic-invoicing',
        },
        {
            title: 'Recepción',
            href: '/electronic-invoicing/received',
        },
        {
            title: document?.encf || 'Detalle',
            href: document ? `/electronic-invoicing/received/${document.id}` : '/electronic-invoicing/received',
        },
    ];

    if (!document) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Detalle de recepción" />
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>No se encontró el documento</AlertTitle>
                        <AlertDescription>El documento recibido solicitado no está disponible.</AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    const convertToExpense = () => {
        router.post(`/electronic-invoicing/received/${document.id}/convert-to-expense`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Documento ${document.encf || ''}`} />

            <div className="max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Documento recibido</h1>
                        <p className="text-gray-600 dark:text-gray-400">Detalle de consulta para {document.encf || 'documento sin e-NCF'}.</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/electronic-invoicing/received">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>

                        {canConvertToExpense && (
                            <Button type="button" variant="outline" onClick={convertToExpense}>
                                <Receipt className="mr-2 h-4 w-4" />
                                Convertir en gasto
                            </Button>
                        )}

                        <Button asChild>
                            <Link href={`/electronic-invoicing/received/${document.id}/print`}>
                                <Printer className="mr-2 h-4 w-4" />
                                Representación impresa
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-3">
                        <CardTitle>Resumen</CardTitle>
                        <Badge className={statusClassMap[document.status] || 'bg-slate-100 text-slate-700 hover:bg-slate-100'}>
                            {statusLabelMap[document.status] || document.status}
                        </Badge>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-xs text-muted-foreground">e-NCF</p>
                            <p className="font-medium">{document.encf || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Tipo e-CF</p>
                            <p className="font-medium">{document.ecf_type || '-'}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Fecha emisión</p>
                            <p className="font-medium">{formatDate(document.issue_date)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-muted-foreground">Fecha recepción</p>
                            <p className="font-medium">{formatDate(document.received_at)}</p>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Suplidor</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="font-medium">{document.supplier?.name || '-'}</p>
                            <p className="text-sm text-muted-foreground">RNC: {document.supplier?.rnc || '-'}</p>
                            <p className="text-sm text-muted-foreground">Correo: {document.supplier?.email || '-'}</p>
                            <p className="text-sm text-muted-foreground">Teléfono: {document.supplier?.phone || '-'}</p>
                            <p className="text-sm text-muted-foreground">Dirección: {document.supplier?.address || '-'}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Comprador</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <p className="font-medium">{document.buyer_name || '-'}</p>
                            <p className="text-sm text-muted-foreground">RNC: {document.buyer_rnc || '-'}</p>
                            <p className="text-sm text-muted-foreground">Código de seguridad: {document.security_code || '-'}</p>
                            <p className="text-sm text-muted-foreground">Fecha firma: {formatDate(document.signed_at)}</p>
                            {document.qr_code_url && (
                                <a href={document.qr_code_url} target="_blank" rel="noopener noreferrer" className="text-sm text-primary underline">
                                    Ver enlace QR DGII
                                </a>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Ítems</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>#</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead className="text-right">Cantidad</TableHead>
                                    <TableHead className="text-right">P. Unitario</TableHead>
                                    <TableHead className="text-right">ITBIS</TableHead>
                                    <TableHead className="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {document.items.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                            No hay líneas para este documento.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    document.items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>{item.line_number ?? '-'}</TableCell>
                                            <TableCell>{item.description || '-'}</TableCell>
                                            <TableCell className="text-right">{item.quantity}</TableCell>
                                            <TableCell className="text-right">{formatAmount(item.unit_price, document.currency)}</TableCell>
                                            <TableCell className="text-right">{formatAmount(item.tax_amount, document.currency)}</TableCell>
                                            <TableCell className="text-right font-medium">
                                                {formatAmount(item.total_amount, document.currency)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Totales</CardTitle>
                    </CardHeader>
                    <CardContent className="grid max-w-md grid-cols-2 gap-y-2">
                        <span className="text-muted-foreground">Subtotal</span>
                        <span className="text-right">{formatAmount(document.subtotal, document.currency)}</span>

                        <span className="text-muted-foreground">ITBIS</span>
                        <span className="text-right">{formatAmount(document.tax_amount, document.currency)}</span>

                        <span className="font-medium">Total</span>
                        <span className="text-right font-semibold">{formatAmount(document.total_amount, document.currency)}</span>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
