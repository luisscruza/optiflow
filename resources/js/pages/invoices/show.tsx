import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { AlertTriangle, Calendar, CreditCard, DownloadCloud, Edit, FileText, Plus, Printer, RefreshCw, Send, Share2, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import QRCode from 'react-qr-code';

import { usePermissions } from '@/hooks/use-permissions';

import { ActivityLogTimeline } from '@/components/ActivityLogTimeline';
import { CommentList } from '@/components/CommentList';
import { CompanyHeader } from '@/components/company-header';
import { PaymentRegistrationModal } from '@/components/payment-registration-modal';
import ShareModal from '@/components/share-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { ActivityLog, BankAccount, Invoice, Payment, type BreadcrumbItem, type ShareData, type SharedData } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    invoice: Invoice;
    activities: ActivityLog[];
    activityFieldLabels: Record<string, string>;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
    share: ShareData;
    paymentShares: Record<string, ShareData>;
}

export default function ShowInvoice({ invoice, activities, activityFieldLabels, bankAccounts, paymentMethods, share, paymentShares }: Props) {
    const { format: formatCurrency } = useCurrency();
    const { auth } = usePage<SharedData>().props;
    const { can } = usePermissions();
    const [isPaymentModalOpen, setIsPaymentModalOpen] = useState(false);
    const [selectedPayment, setSelectedPayment] = useState<Payment | null>(null);
    const [activeShareModal, setActiveShareModal] = useState<{ share: ShareData; title: string } | null>(null);
    const hasElectronicSyncError = invoice.is_electronic && invoice.status === 'draft' && Boolean(invoice.dgii_status?.startsWith('Error sincronización:'));
    const canEditInvoice = can('edit invoices') && (invoice.status === 'pending_payment' || (invoice.is_electronic && invoice.status === 'draft'));
    const canDeleteInvoice = can('delete invoices') && invoice.status === 'draft';
    const canEmitInvoice = can('edit invoices') && invoice.is_electronic && invoice.status === 'draft' && Boolean(invoice.easyfactu_invoice_id);
    const canRefreshElectronicStatus = invoice.is_electronic && Boolean(invoice.easyfactu_invoice_id) && (invoice.status === 'submitted' || hasElectronicSyncError);
    const canRegisterPayment = can('create payments') && invoice.amount_due > 0 && !['draft', 'submitted', 'dgii_rejected'].includes(invoice.status);
    const showElectronicLockBanner = invoice.is_electronic && invoice.status !== 'draft';
    const shouldPollElectronicStatus = invoice.is_electronic && invoice.status === 'submitted' && Boolean(invoice.easyfactu_invoice_id);

    const { start: startPollingElectronicStatus, stop: stopPollingElectronicStatus } = usePoll(
        5000,
        {
            only: ['invoice'],
        },
        {
            autoStart: false,
        },
    );

    useEffect(() => {
        if (shouldPollElectronicStatus) {
            startPollingElectronicStatus();

            return () => {
                stopPollingElectronicStatus();
            };
        }

        stopPollingElectronicStatus();
    }, [shouldPollElectronicStatus, startPollingElectronicStatus, stopPollingElectronicStatus]);

    const emitInvoice = () => {
        if (!confirm('¿Deseas emitir esta factura a la DGII ahora?')) {
            return;
        }

        router.post(`/invoices/${invoice.id}/emit`, {}, { preserveScroll: true });
    };

    const refreshElectronicStatus = () => {
        router.post(`/invoices/${invoice.id}/refresh-status`, {}, { preserveScroll: true });
    };

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

    const getSecurityCodeFromQrUrl = (qrCodeUrl?: string | null): string | null => {
        if (!qrCodeUrl) {
            return null;
        }

        try {
            return new URL(qrCodeUrl).searchParams.get('codigoseguridad');
        } catch {
            return null;
        }
    };

    const formatDate = (dateString: string): string => {
        if (!dateString) {
            return '';
        }

        const date = new Date(dateString);

        if (isNaN(date.getTime())) {
            return '';
        }

        return date.toLocaleDateString('es-DO');
    };

    const formatSignedAt = (dateString?: string | null): string => {
        if (!dateString) {
            return 'No disponible';
        }

        const date = new Date(dateString);

        if (isNaN(date.getTime())) {
            return 'No disponible';
        }

        return new Intl.DateTimeFormat('es-DO', {
            timeZone: 'America/Santo_Domingo',
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(date);
    };

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

    const dgiiSecurityCode = invoice.dgii_security_code || getSecurityCodeFromQrUrl(invoice.dgii_qr_code_url) || 'No disponible';
    const dgiiSignedAt = formatSignedAt(invoice.dgii_signed_at || invoice.created_at);

    // Get status badge styling
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'draft':
                if (hasElectronicSyncError) {
                    return <Badge className="bg-amber-100 text-amber-800 hover:bg-amber-100">Borrador con error DGII</Badge>;
                }
                return <Badge variant="secondary">Borrador</Badge>;
            case 'sent':
                return <Badge variant="outline">Enviada</Badge>;
            case 'submitted':
                return <Badge className="bg-blue-100 text-blue-700 hover:bg-blue-100">Enviada a DGII</Badge>;
            case 'dgii_accepted':
                return <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100">Aceptada por DGII</Badge>;
            case 'dgii_rejected':
                return <Badge className="bg-red-100 text-red-700 hover:bg-red-100">Rechazada por DGII</Badge>;
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

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div className="mb-4 flex gap-3">
                        {canEditInvoice && (
                            <Button
                                size="sm"
                                asChild
                                className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100"
                            >
                                <Link prefetch href={`/invoices/${invoice.id}/edit`}>
                                    <Edit className="h-4 w-4" />
                                    Editar factura
                                </Link>
                            </Button>
                        )}
                        {canDeleteInvoice && (
                            <Button className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100">
                                <Link href={`/invoices/${invoice.id}`} method="delete" as="button">
                                    <Trash2 className="h-4 w-4" />
                                    Eliminar
                                </Link>
                            </Button>
                        )}

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setActiveShareModal({ share, title: `Factura ${invoice.document_number}` })}
                            className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100"
                        >
                            <Share2 className="h-4 w-4" />
                            Compartir
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100"
                        >
                            <a href={`/invoices/${invoice.id}/pdf-stream`} target="_blank" rel="noopener noreferrer">
                                <Printer className="h-4 w-4" />
                                Imprimir
                            </a>
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            asChild
                            className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100"
                        >
                            <a href={`/invoices/${invoice.id}/pdf`}>
                                <DownloadCloud className="h-4 w-4" />
                                Descargar PDF
                            </a>
                        </Button>

                        {canEmitInvoice && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={emitInvoice}
                                className="flex items-center justify-center gap-2 border border-green-300 bg-green-50 text-green-800 hover:border-green-300 hover:bg-green-100"
                            >
                                <Send className="h-4 w-4" />
                                Emitir a DGII
                            </Button>
                        )}

                        {canRefreshElectronicStatus && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={refreshElectronicStatus}
                                className="flex items-center justify-center gap-2 border border-blue-300 bg-blue-50 text-blue-800 hover:border-blue-300 hover:bg-blue-100"
                            >
                                <RefreshCw className="h-4 w-4" />
                                Refrescar estado
                            </Button>
                        )}

                        {canRegisterPayment && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    setSelectedPayment(null);
                                    setIsPaymentModalOpen(true);
                                }}
                                className="flex items-center justify-center gap-2 border border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-300 hover:bg-gray-100"
                            >
                                <Plus className="h-4 w-4" />
                                Registrar pago
                            </Button>
                        )}
                    </div>

                    {showElectronicLockBanner && (
                        <Card className="mb-6 border-0 bg-amber-50 shadow-sm ring-1 ring-amber-200">
                            <CardContent className="px-6 py-4">
                                <div className="flex items-start gap-3">
                                    <AlertTriangle className="mt-0.5 h-5 w-5 text-amber-600" />
                                    <div>
                                        <p className="text-sm text-amber-800">
                                            Después de emitir una factura electrónica, sus datos fiscales quedan congelados. Solo puedes consultar su
                                            estado en DGII.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Payment Summary Card */}
                    <Card className="mb-6 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                        <CardContent className="px-6 py-4">
                            <div className="grid grid-cols-1 divide-y sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                                {/* Total Value */}
                                <div className="space-y-1 px-4 py-3 sm:py-0">
                                    <p className="text-sm font-medium text-gray-600">Valor total</p>
                                    <p className="text-2xl font-bold text-gray-900">{formatCurrency(invoice.total_amount)}</p>
                                </div>

                                {/* Amount Collected */}
                                <div className="space-y-1 px-4 py-3 sm:py-0">
                                    <p className="text-sm font-medium text-gray-600">Cobrado</p>
                                    <p className="text-2xl font-bold text-primary">{formatCurrency(invoice.amount_paid)}</p>
                                </div>

                                {/* Amount Due */}
                                <div className="space-y-1 px-4 py-3 sm:py-0">
                                    <p className="text-sm font-medium text-gray-600">Por cobrar</p>
                                    <p className="text-2xl font-bold text-orange-600">{formatCurrency(invoice.amount_due)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                        <CardContent className="px-6 py-6">
                            {/* Header Section */}
                            <div className="mb-8 flex items-start justify-between border-b border-gray-200 pb-6">
                                {/* Company Info */}
                                <CompanyHeader workspace={invoice.workspace} />
                                {/* Invoice Header */}
                                <div className="space-y-1 text-right">
                                    <h2 className="text-xl font-bold text-gray-900">Factura No. {invoice.id}</h2>
                                    <div className="flex items-center justify-end gap-2">{getStatusBadge(invoice.status)}</div>
                                    {invoice.document_subtype ? (
                                        <>
                                            <div className="flex flex-col gap-2 text-sm font-medium text-gray-900">
                                                <span>{invoice.document_subtype.name}</span>
                                                {invoice.is_electronic && (
                                                    <Badge className="bg-green-100 text-green-700 hover:bg-green-100">
                                                        Factura electrónica: {invoice.dgii_status}
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="text-sm font-semibold text-gray-900">
                                                {invoice.is_electronic ? 'eNCF' : 'NCF'} {invoice.document_number}
                                            </div>
                                            <div className="text-xs text-gray-600">
                                                {!invoice.is_electronic && invoice.document_subtype?.valid_until_date && (
                                                    <>
                                                        <span className="font-semibold text-gray-700">Vencimiento NCF:</span>{' '}
                                                        {formatDate(invoice.document_subtype.valid_until_date)}
                                                    </>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <div className="text-sm font-semibold text-gray-900">{invoice.document_number}</div>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                                <div className="space-y-6">
                                    <div className="space-y-3">
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

                                            {invoice.salesmen && invoice.salesmen.length > 0 && (
                                                <div>
                                                    <Label className="text-sm font-medium text-gray-700">Vendedores</Label>
                                                    <div className="mt-1 flex flex-wrap gap-2">
                                                        {invoice.salesmen.map((salesman) => (
                                                            <span
                                                                key={salesman.id}
                                                                className="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-sm text-primary"
                                                            >
                                                                {salesman.full_name}
                                                            </span>
                                                        ))}
                                                    </div>
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

                                <div className="space-y-6">
                                    <div className="space-y-3">
                                        <div className="space-y-3 rounded-lg bg-gray-50 p-4">
                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Creación</Label>
                                                <p className="mt-1 text-sm text-gray-900">{formatDate(invoice.issue_date)}</p>
                                            </div>

                                            <div>
                                                <Label className="text-sm font-medium text-gray-700">Fecha de vencimiento</Label>
                                                <p className="mt-1 text-sm text-gray-900">{formatDate(invoice.due_date)}</p>
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
                                                        {Number(item.discount_rate) > 0 ? (
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

                    {invoice.is_electronic && (
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <CardTitle className="text-lg font-semibold text-gray-900">Facturación electrónica</CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">eNCF</Label>
                                            <p className="mt-1 font-mono text-sm text-gray-900">
                                                {invoice.encf || invoice.document_number || 'Pendiente'}
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">Estado DGII</Label>
                                            <p className="mt-1 text-sm text-gray-900">{invoice.dgii_status || 'Pendiente de respuesta'}</p>
                                            {shouldPollElectronicStatus && (
                                                <p className="mt-2 flex items-center gap-2 text-xs text-blue-700">
                                                    <RefreshCw className="h-3.5 w-3.5 animate-spin" />
                                                    Actualizando estado automáticamente cada 5 segundos.
                                                </p>
                                            )}
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">Track ID</Label>
                                            <p className="mt-1 text-sm text-gray-900">{invoice.dgii_track_id || 'No disponible'}</p>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">Código de seguridad</Label>
                                            <p className="mt-1 text-sm text-gray-900">{dgiiSecurityCode}</p>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">Fecha de firma</Label>
                                            <p className="mt-1 text-sm text-gray-900">{dgiiSignedAt}</p>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">Entorno</Label>
                                            <div className="mt-1">
                                                <Badge variant="outline">{invoice.dgii_environment || 'No disponible'}</Badge>
                                            </div>
                                        </div>
                                        <div>
                                            <Label className="text-sm font-medium text-gray-700">ID EasyFactu</Label>
                                            <p className="mt-1 text-sm text-gray-900">{invoice.easyfactu_invoice_id || 'No disponible'}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start justify-start lg:justify-end">
                                        {invoice.dgii_qr_code_url ? (
                                            <div className="rounded-lg border border-gray-200 p-3 text-center">
                                                <div className="rounded bg-white p-3">
                                                    <QRCode
                                                        value={invoice.dgii_qr_code_url}
                                                        size={144}
                                                        bgColor="#FFFFFF"
                                                        fgColor="#111827"
                                                        title="Código QR de la factura electrónica"
                                                    />
                                                </div>
                                                <a
                                                    href={invoice.dgii_qr_code_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="mt-3 inline-block text-sm text-primary hover:underline"
                                                >
                                                    Abrir QR
                                                </a>
                                            </div>
                                        ) : (
                                            <div className="rounded-lg border border-dashed border-gray-300 px-6 py-8 text-center text-sm text-gray-500">
                                                El código QR estará disponible cuando la DGII procese la factura.
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Payments Section */}
                    {invoice.payments && invoice.payments.length > 0 && (
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">Pagos recibidos</CardTitle>
                                    </div>
                                    {canRegisterPayment && (
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
                                                        <Button variant="ghost" size="sm" asChild className="h-8 w-8 p-0" title="Imprimir recibo">
                                                            <a href={`/payments/${payment.id}/pdf`} target="_blank" rel="noopener noreferrer">
                                                                <Printer className="h-4 w-4" />
                                                            </a>
                                                        </Button>
                                                        {paymentShares[payment.id] && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() =>
                                                                    setActiveShareModal({
                                                                        share: paymentShares[payment.id],
                                                                        title: `Pago ${payment.payment_number || payment.id}`,
                                                                    })
                                                                }
                                                                className="h-8 w-8 p-0"
                                                                title="Compartir recibo"
                                                            >
                                                                <Share2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
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

                    {(!invoice.payments || invoice.payments.length === 0) && invoice.amount_due > 0 && (
                        <Card className="mb-8 border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">Pagos recibidos</CardTitle>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="py-8 text-center">
                                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                                        <CreditCard className="h-8 w-8 text-orange-600" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-medium text-gray-900">Tu venta aún no tiene pagos recibidos</h3>
                                    {canRegisterPayment ? (
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
                                    ) : invoice.is_electronic ? (
                                        <p className="text-sm text-gray-600">El pago se habilitará cuando la factura sea aceptada por la DGII.</p>
                                    ) : null}
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
                        className="mb-8"
                    />

                    {/* Activity Log Timeline */}
                    {can('view history logs') && (
                        <ActivityLogTimeline
                            activities={activities}
                            fieldLabels={activityFieldLabels}
                            title="Historial de cambios"
                            description="Registro detallado de todas las modificaciones realizadas a esta factura"
                        />
                    )}
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

            {activeShareModal && (
                <ShareModal
                    open={activeShareModal !== null}
                    onOpenChange={(open) => {
                        if (!open) {
                            setActiveShareModal(null);
                        }
                    }}
                    share={activeShareModal.share}
                    title={activeShareModal.title}
                />
            )}
        </AppLayout>
    );
}
