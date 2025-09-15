import { Users, Plus, Search, MoreHorizontal, Eye, Edit, Trash2, MapPin, Mail, Phone } from 'lucide-react';
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
    title: 'Clientes',
    href: '/clients',
  },
];

interface Props {
  clients: PaginatedContacts;
  filters: ContactFilters;
}

export default function ClientsIndex({ clients, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [deletingClient, setDeletingClient] = useState<number | null>(null);

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get('/clients', 
      { search: value || undefined },
      { preserveState: true, replace: true }
    );
  };

  const handleDeleteClient = (clientId: number) => {
    if (deletingClient) return;
    
    setDeletingClient(clientId);
    router.delete(`/clients/${clientId}`, {
      onFinish: () => setDeletingClient(null),
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
      <Head title="Gestión de Clientes" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
          {/* Header */}
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Gestión de Clientes</h1>
              <p className="text-muted-foreground">
                Administra tu cartera de clientes y sus datos de contacto
              </p>
            </div>

            <Button asChild>
              <Link href="/clients/create">
                <Plus className="mr-2 h-4 w-4" />
                Nuevo Cliente
              </Link>
            </Button>
          </div>

          {/* Filters */}
          <Card>
            <CardHeader>
              <CardTitle>Filtros</CardTitle>
              <CardDescription>
                Busca y filtra los clientes
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

          {/* Clients Table */}
          <Card>
            <CardHeader>
              <CardTitle>Clientes</CardTitle>
              <CardDescription>
                Lista de todos los clientes registrados
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Cliente</TableHead>
                      <TableHead>Contacto</TableHead>
                      <TableHead>Dirección</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Límite de Crédito</TableHead>
                      <TableHead className="text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {clients.data.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center py-8">
                          <div className="flex flex-col items-center space-y-2">
                            <Users className="h-8 w-8 text-muted-foreground" />
                            <p className="text-muted-foreground">No se encontraron clientes</p>
                            <Button asChild size="sm" variant="outline">
                              <Link href="/clients/create">Crear tu primer cliente</Link>
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ) : (
                      clients.data.map((client) => (
                        <TableRow key={client.id}>
                          <TableCell className="font-medium">
                            <div className="flex flex-col space-y-1">
                              <div className="flex items-center space-x-2">
                                <Users className="h-4 w-4 text-muted-foreground" />
                                <span>{client.name}</span>
                              </div>
                              {client.identification_object && (
                                <span className="text-xs text-muted-foreground">
                                  {client.identification_object.type}: {client.identification_object.number}
                                </span>
                              )}
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="flex flex-col space-y-1">
                              {client.email && (
                                <div className="flex items-center space-x-1 text-sm">
                                  <Mail className="h-3 w-3 text-muted-foreground" />
                                  <span>{client.email}</span>
                                </div>
                              )}
                              {client.phone_primary && (
                                <div className="flex items-center space-x-1 text-sm">
                                  <Phone className="h-3 w-3 text-muted-foreground" />
                                  <span>{client.phone_primary}</span>
                                </div>
                              )}
                            </div>
                          </TableCell>
                          <TableCell>
                            {client.primary_address ? (
                              <div className="flex items-center space-x-1 text-sm">
                                <MapPin className="h-3 w-3 text-muted-foreground flex-shrink-0" />
                                <span className="truncate max-w-[200px]">
                                  {client.primary_address.full_address}
                                </span>
                              </div>
                            ) : (
                              <span className="text-xs text-muted-foreground">Sin dirección</span>
                            )}
                          </TableCell>
                          <TableCell>
                            <Badge variant={client.status === 'active' ? 'default' : 'secondary'}>
                              {client.status === 'active' ? 'Activo' : 'Inactivo'}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right font-mono">
                            {formatCreditLimit(client.credit_limit)}
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
                                  <Link href={`/clients/${client.id}`}>
                                    <Eye className="mr-2 h-3 w-3" />
                                    Ver detalles
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <Link href={`/clients/${client.id}/edit`}>
                                    <Edit className="mr-2 h-3 w-3" />
                                    Editar
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                  className="text-destructive focus:text-destructive"
                                  onClick={() => handleDeleteClient(client.id)}
                                  disabled={deletingClient === client.id}
                                >
                                  <Trash2 className="mr-2 h-3 w-3" />
                                  {deletingClient === client.id ? 'Eliminando...' : 'Eliminar'}
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
              {clients.last_page > 1 && (
                <div className="flex items-center justify-between space-x-2 py-4">
                  <div className="text-sm text-muted-foreground">
                    Mostrando {clients.from} a {clients.to} de {clients.total} resultados
                  </div>
                  <div className="flex space-x-2">
                    {clients.links.prev && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.get(clients.links.prev!)}
                      >
                        Anterior
                      </Button>
                    )}
                    {clients.links.next && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => router.get(clients.links.next!)}
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