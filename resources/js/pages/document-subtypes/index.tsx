import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Edit, Eye, Filter, Plus, Search, Settings, Star } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Numeraciones de comprobantes',
        href: '/document-subtypes',
    },
];

interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    start_number: number;
    end_number: number | null;
    next_number: number;
    valid_until_date: string | null;
    is_default: boolean;
    electronica: string;
    siguiente_numero: number;
    fecha_finalizacion: string | null;
    preferida: string;
}

interface PaginatedSubtypes {
    data: DocumentSubtype[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface SubtypeFilters {
    search?: string;
    document_type?: string;
}

interface Props {
    subtypes: PaginatedSubtypes;
    filters: SubtypeFilters;
}

export default function DocumentSubtypesIndex({ subtypes, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [documentType, setDocumentType] = useState(filters.document_type || 'Factura de venta');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/document-subtypes',
            { search, document_type: documentType },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleClearFilters = () => {
        setSearch('');
        setDocumentType('Factura de venta');
        router.get(
            '/document-subtypes',
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    const handleSetDefault = (subtypeId: number) => {
        router.patch(
            `/document-subtypes/${subtypeId}/set-default`,
            {},
            {
                preserveState: true,
                onSuccess: () => {
                    router.reload({ only: ['subtypes'] });
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Numeraciones de comprobantes" />

            <div className="max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Numeraciones de comprobantes</h1>
                        <p className="text-sm text-gray-600">Administra las numeraciones de los comprobantes que generas en tu negocio.</p>
                    </div>
                    <Link href="/document-subtypes/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva numeración
                        </Button>
                    </Link>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-4 w-4" />
                            Filtros
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-col gap-4 md:flex-row md:items-end">
                            <div className="flex-1">
                                <label htmlFor="document_type" className="mb-1 block text-sm font-medium text-gray-700">
                                    Tipo de documento
                                </label>
                                <Select value={documentType} onValueChange={setDocumentType}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Factura de venta">Factura de venta</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex-1">
                                <label htmlFor="search" className="mb-1 block text-sm font-medium text-gray-700">
                                    Buscar
                                </label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        id="search"
                                        type="text"
                                        placeholder="Buscar por nombre o prefijo..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit">Filtrar</Button>
                                <Button type="button" variant="outline" onClick={handleClearFilters}>
                                    Limpiar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nombre</TableHead>
                                        <TableHead>Preferida</TableHead>
                                        <TableHead>Fecha de finalización</TableHead>
                                        <TableHead>Prefijo</TableHead>
                                        <TableHead>Siguiente número</TableHead>
                                        <TableHead>Acciones</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subtypes.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-8 text-center text-gray-500">
                                                No se encontraron numeraciones.
                                                <br />
                                                <Link href="/document-subtypes/create" className="text-primary hover:underline">
                                                    Crear la primera numeración
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        subtypes.data.map((subtype) => (
                                            <TableRow key={subtype.id}>
                                                <TableCell className="font-medium">{subtype.name}</TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {subtype.preferida}
                                                        {subtype.is_default && <Star className="h-4 w-4 fill-current text-yellow-500" />}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {subtype.fecha_finalizacion ? (
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="h-4 w-4 text-gray-400" />
                                                            {subtype.fecha_finalizacion}
                                                        </div>
                                                    ) : (
                                                        <span className="text-gray-400">—</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{subtype.prefix}</Badge>
                                                </TableCell>
                                                <TableCell>{subtype.siguiente_numero.toLocaleString()}</TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                <Settings className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/document-subtypes/${subtype.id}`}>
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                Ver detalles</Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/document-subtypes/${subtype.id}/edit`}>
                                                                    <Edit className="mr-2 h-4 w-4" />
                                                                    Editar
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            {!subtype.is_default && (
                                                                <DropdownMenuItem onClick={() => handleSetDefault(subtype.id)}>
                                                                    <Star className="mr-2 h-4 w-4" />
                                                                    Establecer como preferida
                                                                </DropdownMenuItem>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                {/* Pagination */}
                {subtypes.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-gray-700">
                            Mostrando {(subtypes.current_page - 1) * subtypes.per_page + 1} a{' '}
                            {Math.min(subtypes.current_page * subtypes.per_page, subtypes.total)} de {subtypes.total} resultados
                        </p>
                        <div className="flex gap-2">
                            {subtypes.prev_page_url && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={subtypes.prev_page_url}>Anterior</Link>
                                </Button>
                            )}
                            <span className="flex items-center px-3 py-1 text-sm">
                                Página {subtypes.current_page} de {subtypes.last_page}
                            </span>
                            {subtypes.next_page_url && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={subtypes.next_page_url}>Siguiente</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
