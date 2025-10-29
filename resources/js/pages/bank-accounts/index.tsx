import { Head, Link, router } from '@inertiajs/react';
import { Building2, CreditCard, DollarSign, Edit, Eye, Filter, Plus, Trash2, Wallet } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BankAccount, type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cuentas bancarias',
        href: '/bank-accounts',
    },
];

interface Props {
    bankAccounts: BankAccount[];
}

export default function BankAccountsIndex({ bankAccounts }: Props) {
    const [search, setSearch] = useState('');
    const { format: formatCurrency } = useCurrency();

    const handleDelete = (bankAccountId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta cuenta bancaria?')) {
            router.delete(`/bank-accounts/${bankAccountId}`);
        }
    };

    const filteredAccounts = bankAccounts.filter(
        (account) =>
            account.name.toLowerCase().includes(search.toLowerCase()) || account.account_number?.toLowerCase().includes(search.toLowerCase()),
    );

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'bank':
                return <Building2 className="h-5 w-5" />;
            case 'credit_card':
                return <CreditCard className="h-5 w-5" />;
            case 'cash':
                return <Wallet className="h-5 w-5" />;
            default:
                return <DollarSign className="h-5 w-5" />;
        }
    };

    const totalBalance = bankAccounts.filter((account) => account.is_active).reduce((sum, account) => sum + account.balance, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cuentas bancarias" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Cuentas bancarias</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Gestiona tus cuentas bancarias y realiza un seguimiento de tus transacciones.
                        </p>
                    </div>

                    <div className="flex space-x-3">
                        <Button asChild className="bg-primary hover:bg-primary/90">
                            <Link href="/bank-accounts/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva cuenta
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="mb-8 grid gap-6 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Balance Total</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(totalBalance)}</div>
                            <p className="text-xs text-muted-foreground">En cuentas activas</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Cuentas activas</CardTitle>
                            <Wallet className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{bankAccounts.filter((a) => a.is_active).length}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Search */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Buscar</span>
                        </CardTitle>
                        <CardDescription>Busca cuentas por nombre o número de cuenta</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    type="text"
                                    placeholder="Buscar cuenta..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full"
                                />
                            </div>
                            {search && (
                                <Button type="button" variant="outline" onClick={() => setSearch('')}>
                                    Limpiar
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Bank Accounts Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de cuentas</CardTitle>
                        <CardDescription>
                            {filteredAccounts.length === 0
                                ? 'No se encontraron cuentas bancarias.'
                                : `Mostrando ${filteredAccounts.length} cuenta(s)`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {filteredAccounts.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">
                                    {search ? 'No se encontraron cuentas que coincidan con tu búsqueda' : 'No hay cuentas bancarias registradas'}
                                </div>
                                {!search && (
                                    <Button asChild>
                                        <Link href="/bank-accounts/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Crear primera cuenta
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Nombre</TableHead>
                                            <TableHead>Número de cuenta</TableHead>
                                            <TableHead>Moneda</TableHead>
                                            <TableHead className="text-right">Balance</TableHead>
                                            <TableHead>Estado</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {filteredAccounts.map((account) => (
                                            <TableRow key={account.id}>
                                                <TableCell>
                                                    <div className="flex items-center space-x-2">
                                                        {getTypeIcon(account.type)}
                                                        <span className="text-sm">{account.type_label}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{account.name}</div>
                                                    {account.description && (
                                                        <div className="text-sm text-gray-500 dark:text-gray-400">
                                                            {account.description.substring(0, 50)}
                                                            {account.description.length > 50 ? '...' : ''}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">{account.account_number || '-'}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{account.currency?.code || 'N/A'}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right font-medium">
                                                    <span className={account.balance >= 0 ? 'text-green-600' : 'text-red-600'}>
                                                        {formatCurrency(account.balance)}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    {account.is_system_account ? (
                                                        <Badge variant="secondary">Sistema</Badge>
                                                    ) : account.is_active ? (
                                                        <Badge variant="default" className="border-green-200 bg-green-100 text-green-800">
                                                            Activa
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline">Inactiva</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                ⋮
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/bank-accounts/${account.id}`}>
                                                                    <Eye className="mr-2 h-4 w-4" />
                                                                    Ver detalles
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            {!account.is_system_account && (
                                                                <>
                                                                    <DropdownMenuItem asChild>
                                                                        <Link href={`/bank-accounts/${account.id}/edit`}>
                                                                            <Edit className="mr-2 h-4 w-4" />
                                                                            Editar
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        onClick={() => handleDelete(account.id)}
                                                                        className="text-red-600 dark:text-red-400"
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" />
                                                                        Eliminar
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
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
