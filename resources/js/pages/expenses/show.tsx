import { Head, Link } from '@inertiajs/react';
import { Edit, Paperclip, Receipt } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Expense } from '@/types';
import { useCurrency } from '@/utils/currency';

interface Props {
    expense: Expense;
    receivedDocumentId: string | null;
}

const statusStyles: Record<Expense['status'], string> = {
    pending: 'bg-primary/10 text-primary/80 hover:bg-primary/10',
    paid: 'bg-green-100 text-green-800 hover:bg-green-100',
    cancelled: 'bg-red-100 text-red-800 hover:bg-red-100',
};

const statusLabels: Record<Expense['status'], string> = {
    pending: 'Pendiente',
    paid: 'Pagado',
    cancelled: 'Cancelado',
};

export default function ExpensesShow({ expense, receivedDocumentId }: Props) {
    const { can } = usePermissions();
    const { format: formatCurrency } = useCurrency();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Gastos',
            href: '/expenses',
        },
        {
            title: expense.document_number,
            href: `/expenses/${expense.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={expense.document_number} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <div className="mb-3 flex items-center gap-3">
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{expense.document_number}</h1>
                            <Badge className={statusStyles[expense.status]}>{statusLabels[expense.status]}</Badge>
                            <Badge variant="outline">{expense.is_informal ? 'Informal' : 'Fiscal'}</Badge>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">Detalle del gasto registrado para el suplidor seleccionado.</p>
                        {receivedDocumentId && (
                            <div className="mt-3">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/electronic-invoicing/received/${receivedDocumentId}`}>
                                        <Receipt className="mr-2 h-4 w-4" />
                                        Ver documento recibido
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </div>

                    {can('edit expenses') && (
                        <Button asChild>
                            <Link href={`/expenses/${expense.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar gasto
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div className="space-y-8">
                        <Card>
                            <CardHeader>
                                <CardTitle>Información general</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-6 md:grid-cols-2">
                                <div>
                                    <p className="text-sm text-gray-500">Proveedor</p>
                                    <p className="font-medium text-gray-900">{expense.contact?.name ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Sucursal</p>
                                    <p className="font-medium text-gray-900">{expense.workspace?.name ?? '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Fecha</p>
                                    <p className="font-medium text-gray-900">{expense.issue_date}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">RNC o cédula</p>
                                    <p className="font-medium text-gray-900">{expense.contact?.identification_number ?? '-'}</p>
                                </div>
                                <div className="md:col-span-2">
                                    <p className="text-sm text-gray-500">Notas</p>
                                    <p className="font-medium text-gray-900">{expense.notes || 'Sin notas registradas.'}</p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Adjuntos</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {expense.media && expense.media.length > 0 ? (
                                    <div className="space-y-3">
                                        {expense.media.map((attachment) => (
                                            <a
                                                key={attachment.id}
                                                href={attachment.original_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="flex items-center justify-between rounded-lg border p-3 text-sm text-primary hover:bg-gray-50 hover:underline"
                                            >
                                                <span className="flex items-center gap-2">
                                                    <Paperclip className="h-4 w-4" />
                                                    {attachment.file_name}
                                                </span>
                                                <span>{Math.round(attachment.size / 1024)} KB</span>
                                            </a>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-gray-500">No hay adjuntos cargados para este gasto.</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Resumen monetario</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm text-gray-600">
                                <div className="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span>{formatCurrency(expense.subtotal_amount)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>ITBIS</span>
                                    <span>{formatCurrency(expense.itbis_amount)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>ISC</span>
                                    <span>{formatCurrency(expense.isc_amount)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Retención ITBIS</span>
                                    <span>-{formatCurrency(expense.withheld_itbis_amount)}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Retención ISR</span>
                                    <span>-{formatCurrency(expense.withheld_isr_amount)}</span>
                                </div>
                                <div className="rounded-lg bg-primary/5 p-4 text-gray-900">
                                    <p className="text-sm text-gray-500">Total neto</p>
                                    <p className="text-2xl font-semibold">{formatCurrency(expense.total_amount)}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
