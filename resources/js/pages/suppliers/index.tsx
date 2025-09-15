import { Truck, Plus, Search, MoreHorizontal, Eye, Edit, Trash2, MapPin, Mail, Phone, Package } from 'lucide-react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem, type PaginatedContacts, type ContactFilters } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Proveedores',
    href: '/suppliers',
  },
];

interface Props {
  suppliers: PaginatedContacts;
  filters: ContactFilters;
}

export default function SuppliersIndex({ suppliers, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [deletingSupplier, setDeletingSupplier] = useState<number | null>(null);

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get('/suppliers', 
      { search: value || undefined },
      { preserveState: true, replace: true }
    );
  };

  const handleDeleteSupplier = (supplierId: number) => {
    if (deletingSupplier) return;
    
    setDeletingSupplier(supplierId);
    router.delete(`/suppliers/${supplierId}`, {
      onFinish: () => setDeletingSupplier(null),
    });
  };

  const formatCreditLimit = (limit: number) => {
    return new Intl.NumberFormat('es-DO', {
      style: 'currency',
      currency: 'DOP',
    }).format(limit);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Gestión de Proveedores" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
          {/* Header */}
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Gestión de Proveedores</h1>
              <p className="text-muted-foreground">
                Administra tu red de proveedores y sus datos de contacto
              </p>
            </div>

            <Button asChild>
              <Link href="/suppliers/create">
                <Plus className="mr-2 h-4 w-4" />
                Nuevo Proveedor
              </Link>
            </Button>
          </div>

          {/* Filters */}
          <Card>
            <CardHeader>
              <CardTitle>Filtros</CardTitle>
              <CardDescription>
                Busca y filtra los proveedores
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center space-x-4">
                <div className="relative flex-1">
                  <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                  <Input
                    placeholder="Buscar por nombre, email o identificación..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="pl-8"
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Suppliers Table */}
          <Card>
            <CardHeader>
              <CardTitle>Proveedores</CardTitle>
              <CardDescription>
                Lista de todos los proveedores registrados
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Proveedor</TableHead>
                      <TableHead>Contacto</TableHead>
                      <TableHead>Dirección</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Productos</TableHead>
                      <TableHead className="text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {suppliers.data.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center py-8">
                          <div className="flex flex-col items-center space-y-2">
                            <Truck className="h-8 w-8 text-muted-foreground" />
                            <p className="text-muted-foreground">No se encontraron proveedores</p>
                            <Button asChild size="sm" variant="outline">
                              <Link href="/suppliers/create">Crear tu primer proveedor</Link>
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ) : (
                      suppliers.data.map((supplier) => (
                        <TableRow key={supplier.id}>
                          <TableCell className="font-medium">
                            <div className="flex flex-col space-y-1">
                              <div className="flex items-center space-x-2">
                                <Truck className="h-4 w-4 text-muted-foreground" />
                                <span>{supplier.name}</span>
                              </div>
                              {supplier.identification_object && (
                                <span className="text-xs text-muted-foreground">
                                  {supplier.identification_object.type}: {supplier.identification_object.number}
                                </span>
                              )}
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="flex flex-col space-y-1">
                              {supplier.email && (
                                <div className="flex items-center space-x-1 text-sm">
                                  <Mail className="h-3 w-3 text-muted-foreground" />
                                  <span>{supplier.email}</span>
                                </div>
                              )}
                              {supplier.phone_primary && (
                                <div className="flex items-center space-x-1 text-sm">
                                  <Phone className="h-3 w-3 text-muted-foreground" />
                                  <span>{supplier.phone_primary}</span>
                                </div>
                              )}
                            </div>
                          </TableCell>
                          <TableCell>
                            {supplier.primary_address ? (
                              <div className="flex items-center space-x-1 text-sm">
                                <MapPin className="h-3 w-3 text-muted-foreground flex-shrink-0" />
                                <span className="truncate max-w-[200px]">
                                  {supplier.primary_address.full_address}
                                </span>
                              </div>
                            ) : (
                              <span className="text-xs text-muted-foreground">Sin dirección</span>
                            )}
                          </TableCell>
                          <TableCell>
                            <Badge variant={supplier.status === 'active' ? 'default' : 'secondary'}>
                              {supplier.status === 'active' ? 'Activo' : 'Inactivo'}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            <div className="flex items-center justify-end space-x-1">
                              <Package className="h-3 w-3 text-muted-foreground" />
                              <span className="text-sm font-mono">
                                {supplier.supplied_stocks_count || 0}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="sm">
                                  <MoreHorizontal className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                  <Link href={`/suppliers/${supplier.id}`}>
                                    <Eye className="mr-2 h-3 w-3" />
                                    Ver detalles
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <Link href={`/suppliers/${supplier.id}/edit`}>
                                    <Edit className="mr-2 h-3 w-3" />
                                    Editar
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                  className="text-destructive focus:text-destructive"
                                  onClick={() => handleDeleteSupplier(supplier.id)}
                                  disabled={deletingSupplier === supplier.id}
                                >
                                  <Trash2 className="mr-2 h-3 w-3" />
                                  {deletingSupplier === supplier.id ? 'Eliminando...' : 'Eliminar'}
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </div>

              {/* Pagination */}
              {suppliers.last_page > 1 && (
                <div className="flex items-center justify-between space-x-2 py-4">
                  <div className="text-sm text-muted-foreground">
                    Mostrando {suppliers.from} a {suppliers.to} de {suppliers.total} resultados
                  </div>
                  <div className="flex space-x-2">
                    {suppliers.links.prev && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.get(suppliers.links.prev!)}
                      >
                        Anterior
                      </Button>
                    )}
                    {suppliers.links.next && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.get(suppliers.links.next!)}
                      >
                        Siguiente
                      </Button>
                    )}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}