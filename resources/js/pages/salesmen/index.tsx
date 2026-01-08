import { Head, Link, router } from '@inertiajs/react';
import { Edit, Plus, Search, Trash2, User } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Salesman, type User as UserType } from '@/types';

interface PaginatedSalesmen {
    data: (Salesman & { user?: UserType | null })[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    salesmen: PaginatedSalesmen;
    filters: {
        search?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'Vendedores', href: '/salesmen' },
];

export default function SalesmenIndex({ salesmen, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            '/salesmen',
            { search: value },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (id: number, name: string) => {
        if (confirm(`¿Estás seguro de eliminar al vendedor ${name}?`)) {
            router.delete(`/salesmen/${id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vendedores" />

            <div className="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                <User className="h-5 w-5 text-gray-600 dark:text-gray-300" />
                            </div>
                            <CardTitle className="text-xl font-semibold">Vendedores</CardTitle>
                        </div>
                        <Link href="/salesmen/create">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nuevo vendedor
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div className="mb-4">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                <Input
                                    placeholder="Buscar vendedor..."
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Apellido</TableHead>
                                        <TableHead>Usuario vinculado</TableHead>
                                        <TableHead className="text-right">Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {salesmen.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-center text-gray-500">
                                                No se encontraron vendedores
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        salesmen.data.map((salesman) => (
                                            <TableRow key={salesman.id}>
                                                <TableCell className="font-medium">{salesman.name}</TableCell>
                                                <TableCell>{salesman.surname}</TableCell>
                                                <TableCell>
                                                    {salesman.user ? (
                                                        <div className="flex items-center gap-2">
                                                            <User className="h-4 w-4 text-gray-400" />
                                                            <span>{salesman.user.name}</span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-gray-400">Sin vincular</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Link href={`/salesmen/${salesman.id}/edit`}>
                                                            <Button variant="outline" size="sm">
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(salesman.id, salesman.full_name)}
                                                        >
                                                            <Trash2 className="h-4 w-4 text-red-600" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {salesmen.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <div className="text-sm text-gray-600">
                                    Mostrando {salesmen.data.length} de {salesmen.total} vendedores
                                </div>
                                <div className="flex gap-2">
                                    {Array.from({ length: salesmen.last_page }, (_, i) => i + 1).map((page) => (
                                        <Link
                                            key={page}
                                            href={`/salesmen?page=${page}`}
                                            preserveState
                                            className={`rounded px-3 py-1 text-sm ${
                                                page === salesmen.current_page
                                                    ? 'bg-primary text-white'
                                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                            }`}
                                        >
                                            {page}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
