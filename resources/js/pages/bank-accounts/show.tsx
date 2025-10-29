import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Calendar, CreditCard, DollarSign, Edit, FileText, TrendingDown, TrendingUp } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cuentas bancarias',
        href: '/bank-accounts',
    },
    {
        title: 'Detalles de la cuenta',
        href: '#',
    },
];

interface Payment {
    id: number;
    amount: number;
    payment_date: string;
    payment_method: string;
    payment_method_label: string;
    note?: string | null;
    currency: {
        id: number;
        code: string;
        symbol: string;
    };
    invoice?: {
        id: number;
        document_number: string;
        contact: {
            id: number;
            name: string;
        };
    } | null;
}

interface Props {
    bankAccount: {
        id: number;
        name: string;
        type: string;
        type_label: string;
        account_number?: string | null;
        currency: {
            id: number;
            code: string;
            name: string;
            symbol: string;
        };
        balance: number;
        initial_balance: number;
        initial_balance_date: string;
        description: string;
        is_active: boolean;
        is_system_account: boolean;
        created_at: string;
        updated_at: string;
        payments: Payment[];
    };
}

export default function ShowBankAccount({ bankAccount }: Props) {
    const { format: formatCurrency } = useCurrency();

    const handleDelete = () => {
        if (confirm('¿Estás seguro de que deseas eliminar esta cuenta bancaria?')) {
            router.delete(`/bank-accounts/${bankAccount.id}`);
        }
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getPaymentMethodBadge = (method: string) => {
        const config: Record<string, { variant: 'default' | 'secondary' | 'outline'; className?: string }> = {
            cash: { variant: 'secondary' },
            transfer: { variant: 'default', className: 'bg-blue-100 text-blue-800 border-blue-200' },
            check: { variant: 'outline' },
            credit_card: { variant: 'default', className: 'bg-purple-100 text-purple-800 border-purple-200' },
            debit_card: { variant: 'default', className: 'bg-green-100 text-green-800 border-green-200' },
            other: { variant: 'outline' },
        };

        const methodConfig = config[method] || config.other;

        return (
            <Badge variant={methodConfig.variant} className={methodConfig.className}>
                {bankAccount.payments.find((p) => p.payment_method === method)?.payment_method_label || method}
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cuenta: ${bankAccount.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8 flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/bank-accounts">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{bankAccount.name}</h1>
                            <p className="text-gray-600 dark:text-gray-400">
                                {bankAccount.type_label}
                                {bankAccount.account_number && ` • ${bankAccount.account_number}`}
                            </p>
                        </div>
                    </div>

                    <div className="flex space-x-3">
                        {!bankAccount.is_system_account && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={`/bank-accounts/${bankAccount.id}/edit`}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Account Summary Cards */}
                <div className="mb-8 grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Balance actual</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(bankAccount.balance)}</div>
                            <p className="text-xs text-muted-foreground">{bankAccount.currency.code}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Balance inicial</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(bankAccount.initial_balance)}</div>
                            <p className="text-xs text-muted-foreground">{formatDate(bankAccount.initial_balance_date)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Transacciones</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{bankAccount.payments.length}</div>
                            <p className="text-xs text-muted-foreground">Total de pagos registrados</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Account Details */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <Building2 className="mr-2 h-5 w-5" />
                            Detalles de la cuenta
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de cuenta</dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">{bankAccount.type_label}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Moneda</dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {bankAccount.currency.code} - {bankAccount.currency.name}
                                </dd>
                            </div>
                            {bankAccount.account_number && (
                                <div>
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Número de cuenta</dt>
                                    <dd className="mt-1 font-mono text-sm text-gray-900 dark:text-white">{bankAccount.account_number}</dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {bankAccount.is_active ? (
                                        <Badge variant="default" className="border-green-200 bg-green-100 text-green-800">
                                            Activa
                                        </Badge>
                                    ) : (
                                        <Badge variant="destructive">Inactiva</Badge>
                                    )}
                                </dd>
                            </div>
                            {bankAccount.description && (
                                <div className="sm:col-span-2">
                                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Descripción</dt>
                                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">{bankAccount.description}</dd>
                                </div>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                {/* Transaction History */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center">
                            <CreditCard className="mr-2 h-5 w-5" />
                            Historial de transacciones
                        </CardTitle>
                        <CardDescription>
                            {bankAccount.payments.length === 0
                                ? 'No hay transacciones registradas'
                                : `${bankAccount.payments.length} transacciones registradas`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {bankAccount.payments.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se han registrado transacciones para esta cuenta</div>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Factura</TableHead>
                                            <TableHead>Cliente</TableHead>
                                            <TableHead>Método</TableHead>
                                            <TableHead className="text-right">Monto</TableHead>
                                            <TableHead>Nota</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bankAccount.payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell className="font-medium">{formatDateTime(payment.payment_date)}</TableCell>
                                                <TableCell>
                                                    {payment.invoice ? (
                                                        <Link
                                                            href={`/invoices/${payment.invoice.id}`}
                                                            className="text-blue-600 hover:underline dark:text-blue-400"
                                                        >
                                                            {payment.invoice.document_number}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {payment.invoice?.contact ? (
                                                        <Link
                                                            href={`/contacts/${payment.invoice.contact.id}`}
                                                            className="text-gray-900 hover:underline dark:text-white"
                                                        >
                                                            {payment.invoice.contact.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-gray-400">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>{getPaymentMethodBadge(payment.payment_method)}</TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end space-x-1">
                                                        {payment.amount > 0 ? (
                                                            <TrendingUp className="h-4 w-4 text-green-600" />
                                                        ) : (
                                                            <TrendingDown className="h-4 w-4 text-red-600" />
                                                        )}
                                                        <span
                                                            className={payment.amount > 0 ? 'font-medium text-green-600' : 'font-medium text-red-600'}
                                                        >
                                                            {formatCurrency(Math.abs(payment.amount))}
                                                        </span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="max-w-xs truncate text-sm text-gray-500">{payment.note || '-'}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
