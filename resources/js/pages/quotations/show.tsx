import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, Edit, FileText, Printer, RefreshCw, User } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Document } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cotizaciones',
        href: '/quotations',
    },
    {
        title: 'Ver cotización',
        href: '#',
    },
];

interface Props {
    quotation: Document;
}

export default function QuotationShow({ quotation }: Props) {
    const { format: formatCurrency } = useCurrency();
    const { can } = usePermissions();

    const handleConvertToInvoice = () => {
        if (confirm('¿Estás seguro de que deseas convertir esta cotización en una factura? Esta acción no se puede deshacer.')) {
            router.post(
                `/quotations/${quotation.id}/convert-to-invoice`,
                {},
                {
                    preserveScroll: true,
                },
            );
        }
    };

    const getStatusBadge = (status: string) => {
        const statusConfig = {
            draft: { label: 'Borrador', variant: 'secondary' as const, className: '' },
            sent: { label: 'Enviado', variant: 'default' as const, className: '' },
            accepted: { label: 'Aceptada', variant: 'default' as const, className: 'bg-green-100 text-green-800 border-green-200' },
            expired: { label: 'Vencida', variant: 'destructive' as const, className: '' },
            cancelled: { label: 'Cancelada', variant: 'outline' as const, className: '' },
        };

        const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft;

        return (
            <Badge variant={config.variant} className={config.className || undefined}>
                {config.label}
            </Badge>
        );
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cotización ${quotation.document_number}`} />

            <div className="px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Cotización {quotation.document_number}</h1>
                        <p className="text-gray-600 dark:text-gray-400">Creada el {formatDate(quotation.issue_date)}</p>
                    </div>

                    <div className="flex space-x-3">
                        {quotation.status !== 'converted' && quotation.status !== 'cancelled' && (
                            <Button onClick={handleConvertToInvoice} className="bg-green-600 hover:bg-green-700">
                                <RefreshCw className="mr-2 h-4 w-4" />
                                Convertir a factura
                            </Button>
                        )}
                        {can('edit quotations') && (
                            <Button variant="outline" asChild>
                                <Link href={`/quotations/${quotation.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline">
                            <a href={`/quotations/${quotation.id}/pdf`} target="_blank" rel="noopener noreferrer" className="flex items-center">
                                <Printer className="mr-2 h-4 w-4" />
                                Ver PDF
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    {/* Main Content */}
                    <div className="lg:col-span-2">
                        {/* Quotation Details */}
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between">
                                    <span className="flex items-center">
                                        <FileText className="mr-2 h-5 w-5" />
                                        Detalles de la cotización
                                    </span>
                                    {getStatusBadge(quotation.status)}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-6">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Número de cotización</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">{quotation.document_number}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Tipo de documento</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">{quotation.document_subtype?.name}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Fecha de emisión</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">{formatDate(quotation.issue_date)}</p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-500">Válida hasta</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">{formatDate(quotation.due_date)}</p>
                                    </div>
                                </div>
                                {quotation.notes && (
                                    <div className="mt-6">
                                        <label className="text-sm font-medium text-gray-500">Notas</label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">{quotation.notes}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Items */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Artículos</CardTitle>
                                <CardDescription>Lista de productos y servicios incluidos en esta cotización</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Descripción</TableHead>
                                                <TableHead className="text-center">Cantidad</TableHead>
                                                <TableHead className="text-right">Precio unitario</TableHead>
                                                <TableHead className="text-right">Descuento</TableHead>
                                                <TableHead className="text-right">Impuesto</TableHead>
                                                <TableHead className="text-right">Subtotal</TableHead>
                                                <TableHead className="text-right">Total</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {quotation.items?.map((item, index) => (
                                                <TableRow key={index}>
                                                    <TableCell>
                                                        <div>
                                                            <div className="font-medium">{item.product?.name}</div>
                                                            {item.description && <div className="text-sm text-gray-500">{item.description}</div>}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-center">
                                                        {typeof item.quantity === 'string'
                                                            ? parseFloat(item.quantity).toLocaleString()
                                                            : item.quantity.toLocaleString()}
                                                    </TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.unit_price)}</TableCell>
                                                    <TableCell className="text-right">
                                                        {item.discount_rate > 0 ? (
                                                            <div className="text-red-600">
                                                                <span className="font-medium">{item.discount_rate}%</span>
                                                                <span className="ml-1 text-xs">(-{formatCurrency(item.discount_amount)})</span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {item.tax_rate > 0 ? (
                                                            <div>
                                                                <span className="font-medium">{item.tax_rate}%</span>
                                                                <span className="ml-1 text-xs text-gray-600">
                                                                    (+{formatCurrency(item.tax_amount)})
                                                                </span>
                                                            </div>
                                                        ) : (
                                                            <span className="text-gray-400">-</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.subtotal)}</TableCell>
                                                    <TableCell className="text-right font-medium">{formatCurrency(item.total)}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div>
                        {/* Customer Info */}
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <User className="mr-2 h-5 w-5" />
                                    Cliente
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">{quotation.contact?.name}</p>
                                    </div>
                                    {quotation.contact?.email && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Email</label>
                                            <p className="text-sm text-gray-900 dark:text-white">{quotation.contact.email}</p>
                                        </div>
                                    )}
                                    {quotation.contact?.phone_primary && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-500">Teléfono</label>
                                            <p className="text-sm text-gray-900 dark:text-white">{quotation.contact.phone_primary}</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Totals */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <DollarSign className="mr-2 h-5 w-5" />
                                    Totales
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Subtotal:</span>
                                        <span className="text-sm font-medium">{formatCurrency(quotation.subtotal_amount)}</span>
                                    </div>
                                    {quotation.discount_amount > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-sm text-gray-600">Descuento:</span>
                                            <span className="text-sm font-medium text-red-600">-{formatCurrency(quotation.discount_amount)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span className="text-sm text-gray-600">Impuestos:</span>
                                        <span className="text-sm font-medium">{formatCurrency(quotation.tax_amount)}</span>
                                    </div>
                                    <div className="border-t pt-3">
                                        <div className="flex justify-between">
                                            <span className="text-base font-bold">Total:</span>
                                            <span className="text-base font-bold">{formatCurrency(quotation.total_amount)}</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
