import { Head, Link, router } from '@inertiajs/react';
import { Edit, Eye, Filter, Plus, Search, Trash2 } from 'lucide-react';

import { usePermissions } from '@/hooks/use-permissions';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Address, Prescription, type BreadcrumbItem, type PrescriptionFilters, type PaginatedPrescriptions } from '@/types';
import { Paginator } from '@/components/ui/paginator';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recetas',
        href: '/prescriptions',
    },
];

interface Props {
    prescriptions: PaginatedPrescriptions;
    filters?: PrescriptionFilters & { type?: string };
}

export default function PrescriptionsIndex({ prescriptions, filters = {} }: Props) {
    const { can } = usePermissions();
    const [search, setSearch] = useState(filters?.search || '');
    const [showQuickModal, setShowQuickModal] = useState(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/prescriptions',
            { search },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        router.get(
            '/prescriptions',
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleDelete = (prescriptionId: number) => {
        if (confirm('¿Estás seguro de que deseas eliminar esta receta?')) {
            router.delete(`/prescriptions/${prescriptionId}`);
        }
    };

   
   

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recetas" />

            <div className="px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Recetas</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Gestiona las recetas médicas de tus pacientes y su información óptica.
                        </p>
                    </div>

                    {can('create prescriptions') && (
                        <div className="flex space-x-3">
                            <Link href="/prescriptions/create">
                                <Button
                                    variant="outline"
                                    className="border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-900/20 dark:text-gray-300"
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Nueva receta
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>

                {/* Search and Filters */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Filter className="h-5 w-5" />
                            <span>Filtros</span>
                        </CardTitle>
                        <CardDescription>Filtra recetas por paciente, sucursal o fecha</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    type="text"
                                    placeholder="Buscar por paciente, sucursal o ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full"
                                />
                            </div>
                          
                            <Button type="submit">
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </Button>
                            <Button variant="outline" onClick={handleClearFilters}>
                                Limpiar
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* prescriptions Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Lista de recetas</CardTitle>
                        <CardDescription>
                            {prescriptions.total === 0
                                ? 'No se encontraron recetas.'
                                : `Mostrando ${prescriptions.from} - ${prescriptions.to} de ${prescriptions.total} recetas`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {prescriptions.data.length === 0 ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron recetas</div>
                                {can('create prescriptions') && (
                                    <Button asChild>
                                        <Link href="/prescriptions/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Crear primera receta
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>ID</TableHead>
                                            <TableHead>Paciente</TableHead>
                                            <TableHead>Fecha</TableHead>
                                            <TableHead>Sucursal</TableHead>
                                            <TableHead className="text-right">Acciones</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {prescriptions.data.map((prescription) => (
                                            <TableRow key={prescription.id}>
                                                <TableCell>
                                                    <div className="font-medium">{prescription.id}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {prescription.patient?.name || 'Sin paciente'}
                                                    </div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">
                                                        {prescription.patient?.identification_number || ''}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">
                                                        {new Date(prescription.created_at).toLocaleDateString()}
                                                        <span className='text-xs font-semibold italic'> ({prescription.human_readable_date})</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-sm">
                                                        {prescription.workspace?.name || 'No asignada'}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                Acciones
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            {can('view prescriptions') && (
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/prescriptions/${prescription.id}`}>
                                                                        <Eye className="mr-2 h-4 w-4" />
                                                                        Ver detalles
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                            )}
                                                            {can('edit prescriptions') && (
                                                                <DropdownMenuItem asChild>
                                                                    <Link href={`/prescriptions/${prescription.id}/edit`}>
                                                                        <Edit className="mr-2 h-4 w-4" />
                                                                        Editar
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                            )}
                                                            {can('delete prescriptions') && (
                                                                <DropdownMenuItem
                                                                    onClick={() => handleDelete(prescription.id)}
                                                                    className="text-red-600 dark:text-red-400"
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" />
                                                                    Eliminar
                                                                </DropdownMenuItem>
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

                       <Paginator data={prescriptions} />
                    </CardContent>
                </Card>

            </div>
        </AppLayout>
    );
}
