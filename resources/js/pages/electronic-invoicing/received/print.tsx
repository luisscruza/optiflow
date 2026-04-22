import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ReceivedDocumentDetail } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';

interface Props {
    document: ReceivedDocumentDetail | null;
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
    }).format(amount).concat(` ${currency}`);
};

export default function ElectronicInvoicingReceivedPrint({ document }: Props) {
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
            title: 'Representación impresa',
            href: document ? `/electronic-invoicing/received/${document.id}/print` : '/electronic-invoicing/received',
        },
    ];

    if (!document) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Representación impresa" />
                <div className="max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                    <Card>
                        <CardContent className="py-10 text-center text-muted-foreground">No hay información disponible para imprimir.</CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Impresión ${document.encf || ''}`} />

            <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="print:hidden flex items-center justify-between gap-3">
                    <Button variant="outline" asChild>
                        <Link href={`/electronic-invoicing/received/${document.id}`}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver al detalle
                        </Link>
                    </Button>

                    <Button type="button" onClick={() => window.print()}>
                        <Printer className="mr-2 h-4 w-4" />
                        Imprimir
                    </Button>
                </div>

                <Card className="print:shadow-none">
                    <CardContent className="space-y-6 p-6 print:p-0">
                        <div className="space-y-1">
                            <h1 className="text-2xl font-bold">Representación impresa</h1>
                            <p className="text-sm text-muted-foreground">Documento recibido - {document.encf || '-'}</p>
                        </div>

                        <Separator />

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="space-y-1">
                                <p className="text-xs text-muted-foreground uppercase">Datos del documento</p>
                                <p><span className="font-medium">e-NCF:</span> {document.encf || '-'}</p>
                                <p><span className="font-medium">Tipo e-CF:</span> {document.ecf_type || '-'}</p>
                                <p><span className="font-medium">Emisión:</span> {formatDate(document.issue_date)}</p>
                                <p><span className="font-medium">Recepción:</span> {formatDate(document.received_at)}</p>
                            </div>

                            <div className="space-y-1">
                                <p className="text-xs text-muted-foreground uppercase">Suplidor</p>
                                <p>{document.supplier?.name || '-'}</p>
                                <p>RNC: {document.supplier?.rnc || '-'}</p>
                                <p>{document.supplier?.address || '-'}</p>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <p className="text-xs text-muted-foreground uppercase">Comprador</p>
                            <p>{document.buyer_name || '-'}</p>
                            <p>RNC: {document.buyer_rnc || '-'}</p>
                        </div>

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
                                        <TableCell colSpan={6} className="py-6 text-center text-muted-foreground">
                                            No hay líneas para imprimir.
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
                                            <TableCell className="text-right">{formatAmount(item.total_amount, document.currency)}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        <div className="ml-auto grid max-w-xs grid-cols-2 gap-y-2 text-sm">
                            <span className="text-muted-foreground">Subtotal</span>
                            <span className="text-right">{formatAmount(document.subtotal, document.currency)}</span>
                            <span className="text-muted-foreground">ITBIS</span>
                            <span className="text-right">{formatAmount(document.tax_amount, document.currency)}</span>
                            <span className="font-medium">Total</span>
                            <span className="text-right font-semibold">{formatAmount(document.total_amount, document.currency)}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
