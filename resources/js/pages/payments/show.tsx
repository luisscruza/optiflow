import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Calendar, CreditCard, DollarSign, Edit, FileText, Printer, User, XCircle } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Payment } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    payment: Payment;
}

export default function PaymentShow({ payment }: Props) {
    const { can } = usePermissions();
    const { format: formatCurrency } = useCurrency();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pagos recibidos',
            href: '/payments',
        },
        {
            title: payment.payment_number,
            href: `/payments/${payment.id}`,
        },
    ];

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleVoid = () => {
        if (confirm('¿Estás seguro de que deseas anular este pago? Esta acción no se puede deshacer.')) {
            router.delete(`/payments/${payment.id}`);
        }
    };

    const getPaymentTypeLabel = (type: string) => {
        return type === 'invoice' ? 'Pago de Factura' : 'Otros Ingresos';
    };

    const getStatusBadge = (status: string) => {
        if (status === 'completed') {
            return (
                <Badge variant="default" className="bg-green-500">
                    Completado
                </Badge>
            );
        }
        return <Badge variant="destructive">Anulado</Badge>;
    };

    const isInvoicePayment = payment.payment_type === 'invoice';
    const isOtherIncome = payment.payment_type === 'other_income';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Pago ${payment.payment_number}`} />

            <div className="max-w-7xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                {' '}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/payments">
                                <ArrowLeft className="mr-2 size-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">{payment.payment_number}</h1>
                            <p className="text-sm text-muted-foreground">{getPaymentTypeLabel(payment.payment_type)}</p>
                        </div>
                        {getStatusBadge(payment.status)}
                    </div>
                    <div className="flex items-center gap-2">
                        {can('view payments') && (
                            <Button variant="outline" asChild>
                                <a href={`/payments/${payment.id}/pdf`} target="_blank" rel="noopener noreferrer">
                                    <Printer className="mr-2 size-4" />
                                    Imprimir recibo
                                </a>
                            </Button>
                        )}
                        {payment.status === 'completed' && can('edit payments') && (
                            <Button variant="outline" asChild>
                                <Link href={`/payments/${payment.id}/edit`}>
                                    <Edit className="mr-2 size-4" />
                                    Editar
                                </Link>
                            </Button>
                        )}
                        {payment.status === 'completed' && can('delete payments') && (
                            <Button variant="destructive" onClick={handleVoid}>
                                <XCircle className="mr-2 size-4" />
                                Anular
                            </Button>
                        )}
                    </div>
                </div>
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <DollarSign className="size-4" />
                                Monto Total
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{formatCurrency(payment.amount)}</p>
                            {isOtherIncome && (
                                <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                                    <p>Subtotal: {formatCurrency(payment.subtotal_amount)}</p>
                                    {payment.tax_amount > 0 && <p>Impuestos: {formatCurrency(payment.tax_amount)}</p>}
                                    {payment.withholding_amount > 0 && <p>Retenciones: -{formatCurrency(payment.withholding_amount)}</p>}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Calendar className="size-4" />
                                Fecha de pago
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-semibold">{formatDate(payment.payment_date)}</p>
                            <p className="text-sm text-muted-foreground">Registrado: {formatDateTime(payment.created_at)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <CreditCard className="size-4" />
                                Método de pago
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-semibold capitalize">{payment.payment_method}</p>
                            <p className="text-sm text-muted-foreground">{payment.bank_account?.name || '-'}</p>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Detalles del pago</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {isInvoicePayment && payment.invoice && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Factura</p>
                                    <Link href={`/invoices/${payment.invoice.id}`} className="flex items-center gap-2 text-blue-600 hover:underline">
                                        <FileText className="size-4" />
                                        {payment.invoice.document_number}
                                    </Link>
                                </div>
                            )}

                            <div>
                                <p className="text-sm font-medium text-muted-foreground">{isInvoicePayment ? 'Cliente' : 'Contacto'}</p>
                                <div className="flex items-center gap-2">
                                    <User className="size-4" />
                                    <p className="font-medium">
                                        {isInvoicePayment && payment.invoice?.contact ? payment.invoice.contact.name : payment.contact?.name || 'N/A'}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Cuenta Bancaria</p>
                                <p className="font-medium">{payment.bank_account?.name || '-'}</p>
                                {payment.bank_account?.account_number && (
                                    <p className="text-sm text-muted-foreground">{payment.bank_account.account_number}</p>
                                )}
                            </div>

                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Moneda</p>
                                <p className="font-medium">
                                    {payment.currency?.name} ({payment.currency?.code})
                                </p>
                            </div>
                        </div>

                        {payment.note && (
                            <>
                                <Separator />
                                <div>
                                    <p className="mb-2 text-sm font-medium text-muted-foreground">Nota</p>
                                    <p className="text-sm">{payment.note}</p>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
                {isOtherIncome && payment.lines && payment.lines.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Líneas de Detalle</CardTitle>
                            <CardDescription>Conceptos de ingreso del pago</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Descripción</TableHead>
                                        <TableHead>Cuenta Contable</TableHead>
                                        <TableHead className="text-right">Cantidad</TableHead>
                                        <TableHead className="text-right">P. Unitario</TableHead>
                                        <TableHead className="text-right">Subtotal</TableHead>
                                        <TableHead className="text-right">Impuesto</TableHead>
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payment.lines.map((line) => (
                                        <TableRow key={line.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">{line.description}</p>
                                                    {line.payment_concept && (
                                                        <p className="text-xs text-muted-foreground">{line.payment_concept.code}</p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <p className="text-sm">
                                                    {line.chart_account?.code} - {line.chart_account?.name}
                                                </p>
                                            </TableCell>
                                            <TableCell className="text-right">{line.quantity}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(line.unit_price)}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(line.subtotal)}</TableCell>
                                            <TableCell className="text-right">{formatCurrency(line.tax_amount)}</TableCell>
                                            <TableCell className="text-right font-medium">{formatCurrency(line.total)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
                {isOtherIncome && payment.withholdings && payment.withholdings.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Retenciones Aplicadas</CardTitle>
                            <CardDescription>Retenciones fiscales deducidas del pago</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Tipo de Retención</TableHead>
                                        <TableHead className="text-right">Base Imponible</TableHead>
                                        <TableHead className="text-right">Porcentaje</TableHead>
                                        <TableHead className="text-right">Monto Retenido</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payment.withholdings.map((withholding) => (
                                        <TableRow key={withholding.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium">{withholding.withholding_type?.name}</p>
                                                    <p className="text-xs text-muted-foreground">{withholding.withholding_type?.code}</p>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">{formatCurrency(withholding.base_amount)}</TableCell>
                                            <TableCell className="text-right">{withholding.percentage}%</TableCell>
                                            <TableCell className="text-right font-medium">{formatCurrency(withholding.amount)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
