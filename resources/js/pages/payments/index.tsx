import { Head, Link, router } from '@inertiajs/react';
import { DollarSign, Eye, Filter, Plus, Printer, Search } from 'lucide-react';
import { useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Paginator } from '@/components/ui/paginator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BankAccount, type BreadcrumbItem, type PaginatedResponse, type Payment } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pagos recibidos',
        href: '/payments',
    },
];

interface PaymentFilters {
    search?: string;
    payment_type?: string;
    bank_account_id?: number;
}

interface Props {
    payments: PaginatedResponse<Payment>;
    filters: PaymentFilters;
    paymentTypes: Record<string, string>;
    bankAccounts: BankAccount[];
}

export default function PaymentsIndex({ payments, filters, paymentTypes, bankAccounts }: Props) {
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters.search || '');
    const [paymentType, setPaymentType] = useState(filters.payment_type || 'all');
    const [bankAccountId, setBankAccountId] = useState(filters.bank_account_id?.toString() || 'all');
    const { format: formatCurrency } = useCurrency();

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const paymentTypeValue = paymentType === 'all' ? undefined : paymentType;
        const bankAccountValue = bankAccountId === 'all' ? undefined : bankAccountId;
        router.get(
            '/payments',
            { search, payment_type: paymentTypeValue, bank_account_id: bankAccountValue },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setPaymentType('all');
        setBankAccountId('all');
        router.get(
            '/payments',
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const formatDate = (dateString: string) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    const getPaymentTypeLabel = (type: string) => {
        return type === 'invoice' ? 'Pago de Factura' : 'Otros Ingresos';
    };

    const getPaymentTypeBadgeVariant = (type: string): 'default' | 'secondary' => {
        return type === 'invoice' ? 'default' : 'secondary';
    };

    const getContactName = (payment: Payment) => {
        if (payment.invoice?.contact) {
            return payment.invoice.contact.name;
        }
        if (payment.contact) {
            return payment.contact.name;
        }
        return '-';
    };

    const hasFilters = search || paymentType !== 'all' || bankAccountId !== 'all';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pagos recibidos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Pagos recibidos</h1>
                        <p className="text-sm text-muted-foreground">Gestiona todos los pagos recibidos</p>
                    </div>
                    {can('create payments') && (
                        <Button asChild>
                            <Link href="/payments/create">
                                <Plus className="mr-2 size-4" />
                                Registrar pago
                            </Link>
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="size-5" />
                            Filtros
                        </CardTitle>
                        <CardDescription>Busca y filtra los pagos registrados</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="space-y-4">
                            <div className="grid gap-4 md:grid-cols-4">
                                <div className="md:col-span-2">
                                    <Input
                                        type="text"
                                        placeholder="Buscar por número de pago, cliente o nota..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                </div>
                                <Select value={paymentType} onValueChange={setPaymentType}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Tipo de pago" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los tipos</SelectItem>
                                        {Object.entries(paymentTypes).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={bankAccountId} onValueChange={setBankAccountId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Cuenta bancaria" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las cuentas</SelectItem>
                                        {bankAccounts.map((account) => (
                                            <SelectItem key={account.id} value={account.id.toString()}>
                                                {account.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button type="submit">
                                    <Search className="mr-2 size-4" />
                                    Buscar
                                </Button>
                                {hasFilters && (
                                    <Button type="button" variant="outline" onClick={handleClearFilters}>
                                        Limpiar filtros
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Lista de Pagos</CardTitle>
                        <CardDescription>
                            Mostrando {payments.from || 0} - {payments.to || 0} de {payments.total} pagos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {payments.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <DollarSign className="mb-4 size-12 text-muted-foreground" />
                                <p className="text-lg font-medium">No hay pagos registrados</p>
                                <p className="text-sm text-muted-foreground">
                                    {hasFilters ? 'Intenta ajustar los filtros de búsqueda' : 'Comienza registrando tu primer pago'}
                                </p>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Número</TableHead>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Cliente/Contacto</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Cuenta</TableHead>
                                            <TableHead>Método</TableHead>
                                            <TableHead className="text-right">Monto</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payments.data.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell className="font-medium">{payment.payment_number}</TableCell>
                                                <TableCell>
                                                    <Badge variant={getPaymentTypeBadgeVariant(payment.payment_type)}>
                                                        {getPaymentTypeLabel(payment.payment_type)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{getContactName(payment)}</TableCell>
                                                <TableCell>{formatDate(payment.payment_date)}</TableCell>
                                                <TableCell>{payment.bank_account?.name || '-'}</TableCell>
                                                <TableCell className="capitalize">{payment.payment_method}</TableCell>
                                                <TableCell className="text-right font-medium">{formatCurrency(payment.amount)}</TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {can('payments:view') && (
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <a
                                                                    href={`/payments/${payment.id}/pdf`}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    title="Imprimir recibo"
                                                                >
                                                                    <Printer className="size-4" />
                                                                </a>
                                                            </Button>
                                                        )}
                                                        {can('payments:view') && (
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={`/payments/${payment.id}`}>
                                                                    <Eye className="size-4" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                <div className="mt-4">
                                    <Paginator data={payments} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
