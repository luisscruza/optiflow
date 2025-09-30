import { Head, Link } from '@inertiajs/react';
import { Building2, FileText, Edit, Trash2, Printer, ArrowLeft, ShoppingCart, CreditCard, Calendar, Plus } from 'lucide-react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { PaymentRegistrationModal } from '@/components/payment-registration-modal';
import { useCurrency } from '@/utils/currency';
import { Invoice, Payment, BankAccount, type BreadcrumbItem } from '@/types';


interface Props {
    invoice: Invoice;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
}

export default function ShowInvoice({ invoice, bankAccounts, paymentMethods }: Props) {
    const { format: formatCurrency } = useCurrency();
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas',
            href: '/invoices',
        },
        {
            title: `Factura ${invoice.document_number}`,
            href: '#',
        },
    ];

    // Helper function to format date for display
    const formatDate = (dateString: string): string => {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('es-DO');
    };

    // Helper function to format payment method
    const formatPaymentMethod = (method: string): string => {
        const methods: Record<string, string> = {
            'cash': 'Efectivo',
            'transfer': 'Transferencia',
            'check': 'Cheque',
            'credit_card': 'Tarjeta de Crédito',
            'debit_card': 'Tarjeta de Débito',
            'other': 'Otro'
        };
        return methods[method] || method;
    };

    // Get status badge styling
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'draft':
                return <Badge variant="secondary">Borrador</Badge>;
            case 'sent':
                return <Badge variant="outline">Enviada</Badge>;
            case 'paid':
                return <Badge variant="default" className="bg-green-600">Pagada</Badge>;
            case 'overdue':
                return <Badge variant="destructive">Vencida</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Cancelada</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Factura ${invoice.document_number}`} />

            <div className="min-h-screen bg-gray-50/30">
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {/* Enhanced Header - Invoice Style */}
                    <div className="mb-6">
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardContent className="px-6 py-6">
                                <div className="flex items-start justify-between">
                                    {/* Company Info */}
                                    <div className="space-y-1">
                                        <h1 className="text-2xl font-bold text-gray-900">Centro Óptico Visión Integral</h1>
                                        <p className="text-sm text-gray-600">RNC o Cédula: 130382573</p>
                                        <p className="text-sm text-gray-600">info@covi.com.do</p>
                                    </div>

                                    {/* Invoice Details */}
                                    <div className="text-right space-y-1">
                                        <h2 className="text-xl font-bold text-gray-900">Factura No. {invoice.document_number}</h2>
                                        <div className="flex items-center gap-2 justify-end">
                                            {getStatusBadge(invoice.status)}
                                        </div>
                                        <div className="space-y-3 flex flex-col justify-end">
                                            <div className="text-sm text-gray-600">
                                                <div className="grid grid-cols-2 gap-2 text-right">
                                                    <span className="font-medium">Fecha de emisión:</span>
                                                    <span>{formatDate(invoice.issue_date)}</span>
                                                </div>
                                                <div className="grid grid-cols-2 gap-2 text-right">
                                                    <span className="font-medium">Fecha de vencimiento:</span>
                                                    <span>{formatDate(invoice.due_date)}</span>
                                                </div>
                                                {invoice.document_number && (
                                                    <div className="grid grid-cols-2 gap-2 text-right">
                                                        <span className="font-medium">NCF:</span>
                                                        <span className="font-mono text-xs">{invoice.document_number}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Customer and Document Details - Read Only */}
                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5 mb-8">
                        <CardContent className="px-6 py-6">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                {/* Left Column - Customer Details */}
                                <div className="space-y-6">
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                                <Building2 className="h-4 w-4" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900">Información del cliente</h3>
                                        </div>
                                        
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Cliente</Label>
                                                <p className="text-sm text-gray-900 mt-1">{invoice.contact.name}</p>
                                            </div>
                                            
                                            {invoice.contact.email && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Email</Label>
                                                    <p className="text-sm text-gray-900 mt-1">{invoice.contact.email}</p>
                                                </div>
                                            )}
                                            
                                            {invoice.contact.phone_primary && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Teléfono</Label>
                                                    <p className="text-sm text-gray-900 mt-1">{invoice.contact.phone_primary}</p>
                                                </div>
                                            )}
                                            
                                            {invoice.contact.primary_address && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Dirección</Label>
                                                    <p className="text-sm text-gray-900 mt-1">
                                                        {invoice.contact.primary_address.description || invoice.contact.primary_address.full_address}
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Right Column - Invoice Details */}
                                <div className="space-y-6">
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                                <FileText className="h-4 w-4" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900">Detalles de la factura</h3>
                                        </div>
                                        
                                        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Tipo de documento</Label>
                                                <p className="text-sm text-gray-900 mt-1">{invoice.document_subtype.name}</p>
                                            </div>
                                            
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Términos de pago</Label>
                                                <p className="text-sm text-gray-900 mt-1">
                                                    {invoice.payment_term === 'cash' && 'Contado'}
                                                    {invoice.payment_term === '15days' && '15 días'}
                                                    {invoice.payment_term === '30days' && '30 días'}
                                                    {invoice.payment_term === '45days' && '45 días'}
                                                    {invoice.payment_term === '60days' && '60 días'}
                                                    {invoice.payment_term === 'manual' && 'Manual'}
                                                    {!['cash', '15days', '30days', '45days', '60days', 'manual'].includes(invoice.payment_term) && invoice.payment_term}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Items - Read Only */}
                    <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5 mb-8">
                        <CardHeader className="bg-gray-50/50 px-6 py-5">
                            <div>
                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                                        <ShoppingCart className="h-4 w-4" />
                                    </div>
                                    Artículos de la factura
                                </CardTitle>
                                <CardDescription className="mt-1 text-sm text-gray-600">
                                    Lista de productos y servicios incluidos en esta factura.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="px-6 py-6">
                            <div className="space-y-6">
                                {/* Headers - Desktop */}
                                <div className="hidden lg:grid lg:grid-cols-12 gap-3 text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50 px-4 py-3 rounded-lg border">
                                    <div className="col-span-4">Descripción</div>
                                    <div className="col-span-2 text-center">Cantidad</div>
                                    <div className="col-span-2 text-right">Precio Unit.</div>
                                    <div className="col-span-1 text-right">Desc. %</div>
                                    <div className="col-span-1 text-right">Imp. %</div>
                                    <div className="col-span-2 text-right">Total</div>
                                </div>

                                {/* Items */}
                                <div className="space-y-4">
                                    {invoice.items?.map((item) => (
                                        <div
                                            key={item.id}
                                            className="grid grid-cols-1 lg:grid-cols-12 gap-3 p-4 border border-gray-200 rounded-lg bg-white"
                                        >
                                            {/* Description */}
                                            <div className="col-span-1 lg:col-span-4">
                                                <div className="space-y-1">
                                                    <p className="text-sm font-medium text-gray-900">{item.description}</p>
                                                    {item.product && (
                                                        <p className="text-xs text-gray-500">SKU: {item.product.sku}</p>
                                                    )}
                                                </div>
                                            </div>

                                            {/* Quantity */}
                                            <div className="col-span-1 lg:col-span-2">
                                                <div className="lg:text-center">
                                                    <span className="lg:hidden text-xs font-medium text-gray-500">Cantidad: </span>
                                                    <span className="text-sm text-gray-900">{item.quantity}</span>
                                                </div>
                                            </div>

                                            {/* Unit Price */}
                                            <div className="col-span-1 lg:col-span-2">
                                                <div className="lg:text-right">
                                                    <span className="lg:hidden text-xs font-medium text-gray-500">Precio: </span>
                                                    <span className="text-sm text-gray-900">{formatCurrency(item.unit_price)}</span>
                                                </div>
                                            </div>

                                            {/* Discount Rate */}
                                            <div className="col-span-1 lg:col-span-1">
                                                <div className="lg:text-right">
                                                    <span className="lg:hidden text-xs font-medium text-gray-500">Desc.: </span>
                                                    <span className="text-sm text-gray-900">{item.discount_rate}%</span>
                                                </div>
                                            </div>

                                            {/* Tax Rate */}
                                            <div className="col-span-1 lg:col-span-1">
                                                <div className="lg:text-right">
                                                    <span className="lg:hidden text-xs font-medium text-gray-500">Imp.: </span>
                                                    <span className="text-sm text-gray-900">{item.tax_rate}%</span>
                                                </div>
                                            </div>

                                            {/* Total */}
                                            <div className="col-span-1 lg:col-span-2">
                                                <div className="lg:text-right">
                                                    <span className="lg:hidden text-xs font-medium text-gray-500">Total: </span>
                                                    <span className="text-sm font-medium text-gray-900">{formatCurrency(item.total)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Totals */}
                                <div className="border-t border-gray-200 pt-6">
                                    <div className="flex justify-end">
                                        <div className="w-full max-w-sm space-y-3">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Subtotal:</span>
                                                <span className="font-medium text-gray-900">{formatCurrency(invoice.subtotal_amount)}</span>
                                            </div>
                                            {invoice.discount_amount > 0 && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Descuentos:</span>
                                                    <span className="font-medium text-gray-900">-{formatCurrency(invoice.discount_amount)}</span>
                                                </div>
                                            )}
                                            {invoice.tax_amount > 0 && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Impuestos:</span>
                                                    <span className="font-medium text-gray-900">{formatCurrency(invoice.tax_amount)}</span>
                                                </div>
                                            )}
                                            <div className="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                                <span className="text-gray-900">Total:</span>
                                                <span className="text-gray-900">{formatCurrency(invoice.total_amount)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Payments Section */}
                    {invoice.payments && invoice.payments.length > 0 && (
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5 mb-8">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-600">
                                                <CreditCard className="h-4 w-4" />
                                            </div>
                                            Pagos recibidos
                                        </CardTitle>
                                        <CardDescription className="mt-1 text-sm text-gray-600">
                                            Historial de pagos registrados para esta factura.
                                        </CardDescription>
                                    </div>
                                    {invoice.amount_due > 0 && (
                                        <Button
                                            onClick={() => setIsPaymentModalOpen(true)}
                                            size="sm"
                                            className="flex items-center gap-2"
                                        >
                                            <Plus className="h-4 w-4" />
                                            Registrar pago
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="space-y-4">
                                    {/* Headers - Desktop */}
                                    <div className="hidden lg:grid lg:grid-cols-12 gap-3 text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50 px-4 py-3 rounded-lg border">
                                        <div className="col-span-2">Fecha</div>
                                        <div className="col-span-3">Cuenta bancaria</div>
                                        <div className="col-span-2">Método</div>
                                        <div className="col-span-2 text-right">Monto</div>
                                        <div className="col-span-3">Observaciones</div>
                                    </div>

                                    {/* Payment Items */}
                                    <div className="space-y-3">
                                        {invoice.payments.map((payment) => (
                                            <div
                                                key={payment.id}
                                                className="grid grid-cols-1 lg:grid-cols-12 gap-3 p-4 border border-gray-200 rounded-lg bg-white"
                                            >
                                                {/* Date */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="h-4 w-4 text-gray-400 lg:hidden" />
                                                        <div>
                                                            <span className="lg:hidden text-xs font-medium text-gray-500">Fecha: </span>
                                                            <span className="text-sm text-gray-900">{formatDate(payment.payment_date)}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Bank Account */}
                                                <div className="col-span-1 lg:col-span-3">
                                                    <div>
                                                        <span className="lg:hidden text-xs font-medium text-gray-500">Cuenta: </span>
                                                        <span className="text-sm text-gray-900">
                                                            {payment.bank_account?.name || 'Cuenta no especificada'}
                                                        </span>
                                                        {payment.bank_account?.bank_name && (
                                                            <p className="text-xs text-gray-500 mt-1">
                                                                {payment.bank_account.bank_name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Payment Method */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div>
                                                        <span className="lg:hidden text-xs font-medium text-gray-500">Método: </span>
                                                        <Badge variant="outline" className="text-xs">
                                                            {formatPaymentMethod(payment.payment_method)}
                                                        </Badge>
                                                    </div>
                                                </div>

                                                {/* Amount */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div className="lg:text-right">
                                                        <span className="lg:hidden text-xs font-medium text-gray-500">Monto: </span>
                                                        <span className="text-sm font-semibold text-green-600">
                                                            {formatCurrency(payment.amount)}
                                                        </span>
                                                    </div>
                                                </div>

                                                {/* Note */}
                                                <div className="col-span-1 lg:col-span-3">
                                                    <div>
                                                        <span className="lg:hidden text-xs font-medium text-gray-500">Nota: </span>
                                                        <span className="text-sm text-gray-700">
                                                            {payment.note || 'Sin observaciones'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Payment Summary */}
                                    <div className="border-t border-gray-200 pt-4">
                                        <div className="flex justify-end">
                                            <div className="w-full max-w-sm space-y-2">
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Total pagado:</span>
                                                    <span className="font-semibold text-green-600">
                                                        {formatCurrency(invoice.payments.reduce((sum, payment) => sum + payment.amount, 0))}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Monto pendiente:</span>
                                                    <span className={`font-semibold ${invoice.amount_due > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                                        {formatCurrency(invoice.amount_due)}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between text-base font-bold border-t border-gray-200 pt-2">
                                                    <span className="text-gray-900">Estado:</span>
                                                    <span className={`${invoice.amount_due === 0 ? 'text-green-600' : 'text-orange-600'}`}>
                                                        {invoice.amount_due === 0 ? 'Pagado completamente' : 'Pago parcial'}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* No Payments Yet Section */}
                    {(!invoice.payments || invoice.payments.length === 0) && invoice.amount_due > 0 && (
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5 mb-8">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                                                <CreditCard className="h-4 w-4" />
                                            </div>
                                            Pagos pendientes
                                        </CardTitle>
                                        <CardDescription className="mt-1 text-sm text-gray-600">
                                            Esta factura aún no tiene pagos registrados.
                                        </CardDescription>
                                    </div>
                                    <Button
                                        onClick={() => setIsPaymentModalOpen(true)}
                                        size="sm"
                                        className="flex items-center gap-2"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Registrar primer pago
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="text-center py-8">
                                    <div className="mx-auto h-16 w-16 rounded-full bg-orange-100 flex items-center justify-center mb-4">
                                        <CreditCard className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">Sin pagos registrados</h3>
                                    <p className="text-gray-600 mb-4">
                                        Monto pendiente: <span className="font-semibold text-orange-600">{formatCurrency(invoice.amount_due)}</span>
                                    </p>
                                    <Button
                                        onClick={() => setIsPaymentModalOpen(true)}
                                        className="flex items-center gap-2 mx-auto"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Registrar pago
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Notes - Read Only */}
                    {invoice.notes && (
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5 mb-8">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                        <FileText className="h-4 w-4" />
                                    </div>
                                    Notas adicionales
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="bg-gray-50 rounded-lg p-4">
                                    <p className="text-sm text-gray-900 whitespace-pre-wrap">{invoice.notes}</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Action Buttons */}
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <Button
                            variant="outline"
                            size="lg"
                            asChild
                            className="border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-400"
                        >
                            <Link href="/invoices" className="flex items-center justify-center gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                Volver a facturas
                            </Link>
                        </Button>

                        <div className="flex gap-3">
                            {invoice.status === 'draft' && (
                                <Button
                                    variant="destructive"
                                    size="lg"
                                    asChild
                                    className="flex items-center justify-center gap-2"
                                >
                                    <Link href={`/invoices/${invoice.id}`} method="delete" as="button">
                                        <Trash2 className="h-4 w-4" />
                                        Eliminar
                                    </Link>
                                </Button>
                            )}

                            {invoice.amount_due > 0 && (
                                <Button
                                    variant="outline"
                                    size="lg"
                                    onClick={() => setIsPaymentModalOpen(true)}
                                    className="flex items-center justify-center gap-2 border-green-200 bg-green-50 text-green-700 hover:bg-green-100 hover:border-green-300"
                                >
                                    <Plus className="h-4 w-4" />
                                    Registrar pago
                                </Button>
                            )}
                            
                            <Button
                                variant="outline"
                                size="lg"
                                className="flex items-center justify-center gap-2 border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-300"
                                onClick={() => window.print()}
                            >
                                <Printer className="h-4 w-4" />
                                Imprimir
                            </Button>

                            <Button
                                size="lg"
                                asChild
                                className="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90"
                            >
                                <Link prefetch href={`/invoices/${invoice.id}/edit`}>
                                    <Edit className="h-4 w-4" />
                                    Editar factura
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Payment Registration Modal */}
            <PaymentRegistrationModal
                isOpen={isPaymentModalOpen}
                onClose={() => setIsPaymentModalOpen(false)}
                invoice={invoice}
                bankAccounts={bankAccounts}
                paymentMethods={paymentMethods}
            />
        </AppLayout>
    );
}