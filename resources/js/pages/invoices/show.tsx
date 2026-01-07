import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Building2, Calendar, CreditCard, Edit, FileText, Plus, Printer, ShoppingCart, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';

import { CommentList } from '@/components/CommentList';
import { PaymentRegistrationModal } from '@/components/payment-registration-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { BankAccount, Invoice, Payment, type BreadcrumbItem, type SharedData } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    invoice: Invoice;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
}

export default function ShowInvoice({ invoice, bankAccounts, paymentMethods }: Props) {
    const { format: formatCurrency } = useCurrency();
    const { auth } = usePage<SharedData>().props;
    const { can } = usePermissions();
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);

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
            cash: 'Efectivo',
            transfer: 'Transferencia',
            check: 'Cheque',
            credit_card: 'Tarjeta de Crédito',
            debit_card: 'Tarjeta de Débito',
            other: 'Otro',
        };
        return methods[method] || method;
    };

    // Calculate tax breakdown by type (for display in totals)
    const getTaxBreakdown = (): Array<{ name: string; amount: number }> => {
        const taxMap = new Map<string, number>();

        invoice.items?.forEach((item) => {
            item.taxes?.forEach((tax) => {
                const currentAmount = taxMap.get(tax.name) || 0;
                taxMap.set(tax.name, currentAmount + (Number(tax.pivot.amount) || 0));
            });
        });

        return Array.from(taxMap.entries())
            .map(([name, amount]) => ({ name, amount }))
            .sort((a, b) => b.amount - a.amount); // Sort by amount descending
    };

    // Get status badge styling
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'draft':
                return <Badge variant="secondary">Borrador</Badge>;
            case 'sent':
                return <Badge variant="outline">Enviada</Badge>;
            case 'paid':
                return (
                    <Badge variant="default" className="bg-green-600">
                        Pagada
                    </Badge>
                );
            case 'overdue':
                return <Badge variant="destructive">Vencida</Badge>;
            case 'cancelled':
                return <Badge variant="destructive">Cancelada</Badge>;
            case 'pending_payment':
                return <Badge variant="destructive">Pago pendiente</Badge>;
            case 'partially_paid':
                return <Badge variant="outline">Parcialmente pagada</Badge>;
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
                                    <div className="space-y-1 text-right">
                                        <h2 className="text-xl font-bold text-gray-900">Factura No. {invoice.document_number}</h2>
                                        <div className="flex items-center justify-end gap-2">{getStatusBadge(invoice.status)}</div>
                                        <div className="flex flex-col justify-end space-y-3">
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
                    <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                        <CardContent className="px-6 py-6">
                            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                {/* Left Column - Customer Details */}
                                <div className="space-y-6">
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                                <Building2 className="h-4 w-4" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900">Información del cliente</h3>
                                        </div>

                                        <div className="space-y-3 rounded-lg bg-gray-50 p-4">
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Cliente</Label>
                                                <p className="mt-1 text-sm text-gray-900">{invoice.contact.name}</p>
                                            </div>

                                            {invoice.contact.email && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Email</Label>
                                                    <p className="mt-1 text-sm text-gray-900">{invoice.contact.email}</p>
                                                </div>
                                            )}

                                            {invoice.contact.phone_primary && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Teléfono</Label>
                                                    <p className="mt-1 text-sm text-gray-900">{invoice.contact.phone_primary}</p>
                                                </div>
                                            )}

                                            {invoice.contact.primary_address && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Dirección</Label>
                                                    <p className="mt-1 text-sm text-gray-900">
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

                                        <div className="space-y-3 rounded-lg bg-gray-50 p-4">
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Tipo de documento</Label>
                                                <p className="mt-1 text-sm text-gray-900">{invoice.document_subtype.name}</p>
                                            </div>

                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Términos de pago</Label>
                                                <p className="mt-1 text-sm text-gray-900">
                                                    {invoice.payment_term === 'cash' && 'Contado'}
                                                    {invoice.payment_term === '15days' && '15 días'}
                                                    {invoice.payment_term === '30days' && '30 días'}
                                                    {invoice.payment_term === '45days' && '45 días'}
                                                    {invoice.payment_term === '60days' && '60 días'}
                                                    {invoice.payment_term === 'manual' && 'Manual'}
                                                    {!['cash', '15days', '30days', '45days', '60days', 'manual'].includes(invoice.payment_term) &&
                                                        invoice.payment_term}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Invoice Items - Read Only */}
                    <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
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
                                {/* Headers - Desktop - Using overflow for wider tables */}
                                <div className="overflow-x-auto">
                                    <div className="min-w-[800px]">
                                        <div className="grid grid-cols-[2.5fr_0.8fr_1.2fr_1.5fr_1.5fr_1.2fr_1.2fr] gap-2 rounded-lg border bg-gray-50 px-4 py-3 text-xs font-semibold tracking-wider text-gray-700 uppercase">
                                            <div>Descripción</div>
                                            <div className="text-center">Cant.</div>
                                            <div className="text-right">Precio Unit.</div>
                                            <div className="text-right">Descuento</div>
                                            <div className="text-right">Impuesto</div>
                                            <div className="text-right">Subtotal</div>
                                            <div className="text-right">Total</div>
                                        </div>

                                        {/* Items */}
                                        <div className="mt-4 space-y-3">
                                            {invoice.items?.map((item) => (
                                                <div
                                                    key={item.id}
                                                    className="grid grid-cols-[2.5fr_0.8fr_1.2fr_1.5fr_1.5fr_1.2fr_1.2fr] gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3"
                                                >
                                                    {/* Description */}
                                                    <div>
                                                        <p className="text-sm font-medium text-gray-900">{item.description}</p>
                                                        {item.product && <p className="text-xs text-gray-500">SKU: {item.product.sku}</p>}
                                                    </div>

                                                    {/* Quantity */}
                                                    <div className="text-center">
                                                        <span className="text-sm text-gray-900">{item.quantity}</span>
                                                    </div>

                                                    {/* Unit Price */}
                                                    <div className="text-right">
                                                        <span className="text-sm text-gray-900">{formatCurrency(item.unit_price)}</span>
                                                    </div>

                                                    {/* Discount (rate + amount) */}
                                                    <div className="text-right">
                                                        {item.discount_rate > 0 ? (
                                                            <div className="text-sm text-red-600">
                                                                <span className="font-medium">{item.discount_rate}%</span>
                                                                <span className="ml-1 text-xs">(-{formatCurrency(item.discount_amount)})</span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-400">-</span>
                                                        )}
                                                    </div>

                                                    {/* Tax - Show all taxes for this item */}
                                                    <div className="text-right">
                                                        {item.taxes && item.taxes.length > 0 ? (
                                                            <div className="space-y-0.5 text-xs text-gray-900">
                                                                {item.taxes.map((tax) => (
                                                                    <div key={tax.id}>
                                                                        <span className="font-medium">
                                                                            {tax.name} ({tax.pivot.rate}%)
                                                                        </span>
                                                                        <span className="ml-1 text-gray-600">
                                                                            (+{formatCurrency(tax.pivot.amount)})
                                                                        </span>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-400">-</span>
                                                        )}
                                                    </div>

                                                    {/* Subtotal (before discount and taxes) */}
                                                    <div className="text-right">
                                                        <span className="text-sm text-gray-900">{formatCurrency(item.subtotal)}</span>
                                                    </div>

                                                    {/* Total (with discount and taxes) */}
                                                    <div className="text-right">
                                                        <span className="text-sm font-semibold text-gray-900">{formatCurrency(item.total)}</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
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
                                            {getTaxBreakdown().length > 0 && (
                                                <div className="space-y-2 border-t border-gray-100 pt-2">
                                                    {getTaxBreakdown().map((tax) => (
                                                        <div key={tax.name} className="flex justify-between text-sm">
                                                            <span className="text-gray-600">{tax.name}:</span>
                                                            <span className="font-medium text-gray-900">+{formatCurrency(tax.amount)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                            <div className="flex justify-between border-t border-gray-200 pt-3 text-lg font-bold">
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
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
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
                                    {invoice.amount_due > 0 && can('create payments') && (
                                        <Button
                                            onClick={() => {
                                                setSelectedPayment(null);
                                                setIsPaymentModalOpen(true);
                                            }}
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
                                    <div className="hidden gap-3 rounded-lg border bg-gray-50 px-4 py-3 text-xs font-semibold tracking-wider text-gray-700 uppercase lg:grid lg:grid-cols-12">
                                        <div className="col-span-2">Fecha</div>
                                        <div className="col-span-2">Cuenta bancaria</div>
                                        <div className="col-span-2">Método</div>
                                        <div className="col-span-2 text-right">Monto</div>
                                        <div className="col-span-2">Observaciones</div>
                                        <div className="col-span-2 text-center">Acciones</div>
                                    </div>

                                    {/* Payment Items */}
                                    <div className="space-y-3">
                                        {invoice.payments.map((payment) => (
                                            <div
                                                key={payment.id}
                                                className="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 bg-white p-4 lg:grid-cols-12"
                                            >
                                                {/* Date */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="h-4 w-4 text-gray-400 lg:hidden" />
                                                        <div>
                                                            <span className="text-xs font-medium text-gray-500 lg:hidden">Fecha: </span>
                                                            <span className="text-sm text-gray-900">{formatDate(payment.payment_date)}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Bank Account */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div>
                                                        <span className="text-xs font-medium text-gray-500 lg:hidden">Cuenta: </span>
                                                        <span className="text-sm text-gray-900">
                                                            {payment.bank_account?.name || 'Cuenta no especificada'}
                                                        </span>
                                                        {payment.bank_account?.bank_name && (
                                                            <p className="mt-1 text-xs text-gray-500">{payment.bank_account.bank_name}</p>
                                                        )}
                                                    </div>
                                                </div>

                                                {/* Payment Method */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div>
                                                        <span className="text-xs font-medium text-gray-500 lg:hidden">Método: </span>
                                                        <Badge variant="outline" className="text-xs">
                                                            {formatPaymentMethod(payment.payment_method)}
                                                        </Badge>
                                                    </div>
                                                </div>

                                                {/* Amount */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div className="lg:text-right">
                                                        <span className="text-xs font-medium text-gray-500 lg:hidden">Monto: </span>
                                                        <span className="text-sm font-semibold text-green-600">{formatCurrency(payment.amount)}</span>
                                                    </div>
                                                </div>

                                                {/* Note */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div>
                                                        <span className="text-xs font-medium text-gray-500 lg:hidden">Nota: </span>
                                                        <span className="text-sm text-gray-700">{payment.note || 'Sin observaciones'}</span>
                                                    </div>
                                                </div>

                                                {/* Actions */}
                                                <div className="col-span-1 lg:col-span-2">
                                                    <div className="flex items-center justify-end gap-2 lg:justify-center">
                                                        {can('edit payments') && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => {
                                                                    setSelectedPayment(payment);
                                                                    setIsPaymentModalOpen(true);
                                                                }}
                                                                className="h-8 w-8 p-0"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        {can('delete payments') && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => {
                                                                    if (confirm('¿Estás seguro de que deseas eliminar este pago?')) {
                                                                        router.delete(`/payments/${payment.id}`, {
                                                                            preserveScroll: true,
                                                                        });
                                                                    }
                                                                }}
                                                                className="h-8 w-8 p-0 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
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
                                                <div className="flex justify-between border-t border-gray-200 pt-2 text-base font-bold">
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
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
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
                                        onClick={() => {
                                            setSelectedPayment(null);
                                            setIsPaymentModalOpen(true);
                                        }}
                                        size="sm"
                                        className="flex items-center gap-2"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Registrar primer pago
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="py-8 text-center">
                                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                                        <CreditCard className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-medium text-gray-900">Sin pagos registrados</h3>
                                    <p className="mb-4 text-gray-600">
                                        Monto pendiente: <span className="font-semibold text-orange-600">{formatCurrency(invoice.amount_due)}</span>
                                    </p>
                                    <Button
                                        onClick={() => {
                                            setSelectedPayment(null);
                                            setIsPaymentModalOpen(true);
                                        }}
                                        className="mx-auto flex items-center gap-2"
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
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                        <FileText className="h-4 w-4" />
                                    </div>
                                    Notas adicionales
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="rounded-lg bg-gray-50 p-4">
                                    <p className="text-sm whitespace-pre-wrap text-gray-900">{invoice.notes}</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Comments Section */}
                    <CommentList
                        comments={invoice.comments || []}
                        commentableType="Invoice"
                        commentableId={invoice.id}
                        currentUser={auth.user}
                        title="Comentarios de la factura"
                        className="mb-8"
                    />

                    {/* Action Buttons */}
                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <Button
                            variant="outline"
                            size="lg"
                            asChild
                            className="border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50"
                        >
                            <Link href="/invoices" className="flex items-center justify-center gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                Volver a facturas
                            </Link>
                        </Button>

                        <div className="flex gap-3">
                            {can('delete invoices') && invoice.status === 'draft' && (
                                <Button variant="destructive" size="lg" asChild className="flex items-center justify-center gap-2">
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
                                    onClick={() => {
                                        setSelectedPayment(null);
                                        setIsPaymentModalOpen(true);
                                    }}
                                    className="flex items-center justify-center gap-2 border-green-200 bg-green-50 text-green-700 hover:border-green-300 hover:bg-green-100"
                                >
                                    <Plus className="h-4 w-4" />
                                    Registrar pago
                                </Button>
                            )}

                            <Button
                                variant="outline"
                                size="lg"
                                className="flex items-center justify-center gap-2 border-blue-200 bg-blue-50 text-blue-700 hover:border-blue-300 hover:bg-blue-100"
                                onClick={() => window.print()}
                            >
                                <Printer className="h-4 w-4" />
                                Imprimir
                            </Button>

                            {can('edit invoices') && (
                                <Button size="lg" asChild className="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90">
                                    <Link prefetch href={`/invoices/${invoice.id}/edit`}>
                                        <Edit className="h-4 w-4" />
                                        Editar factura
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Payment Registration Modal */}
            <PaymentRegistrationModal
                isOpen={isPaymentModalOpen}
                onClose={() => {
                    setIsPaymentModalOpen(false);
                    setSelectedPayment(null);
                }}
                invoice={invoice}
                bankAccounts={bankAccounts}
                paymentMethods={paymentMethods}
                payment={selectedPayment}
            />
        </AppLayout>
    );
}
