import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Search, Plus, Filter, Eye, Edit, Trash2, Users, Building2 } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type BreadcrumbItem, type PaginatedContacts, type ContactFilters } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Contactos',
    href: '/contacts',
  },
];

interface Props {
  contacts: PaginatedContacts;
  filters: ContactFilters & { type?: string };
}

export default function ContactsIndex({ contacts, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [contactType, setContactType] = useState(filters.type || 'all');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const searchType = contactType === 'all' ? undefined : contactType;
    router.get('/contacts', { search, type: searchType }, {
      preserveState: true,
      replace: true
    });
  };

  const handleClearFilters = () => {
    setSearch('');
    setContactType('all');
    router.get('/contacts', {}, {
      preserveState: true,
      replace: true
    });
  };

  const handleDelete = (contactId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este contacto?')) {
      router.delete(`/contacts/${contactId}`);
    }
  };

  const getContactTypeIcon = (type: string) => {
    return type === 'customer' ? <Users className="h-4 w-4" /> : <Building2 className="h-4 w-4" />;
  };

  const getContactTypeBadge = (type: string) => {
    return type === 'customer' ? (
      <Badge variant="default">Cliente</Badge>
    ) : (
      <Badge variant="secondary">Proveedor</Badge>
    );
  };

  const formatContactInfo = (contact: any) => {
    const info = [];
    if (contact.email) info.push(contact.email);
    if (contact.phone_primary) info.push(contact.phone_primary);
    return info.join(' • ');
  };

  const formatAddress = (address: any) => {
    if (!address) return 'Sin dirección';
    const parts = [];
    if (address.municipality) parts.push(address.municipality);
    if (address.province) parts.push(address.province);
    return parts.join(', ') || 'Sin dirección';
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Contactos" />

      <div className="px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Contactos
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              Gestiona clientes y proveedores
            </p>
          </div>

          <Button asChild>
            <Link href="/contacts/create">
              <Plus className="mr-2 h-4 w-4" />
              Nuevo Contacto
            </Link>
          </Button>
        </div>

        {/* Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Contactos
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{contacts.total}</div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Clientes
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-600">
                {contacts.data.filter(c => c.contact_type === 'customer').length}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Proveedores
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-green-600">
                {contacts.data.filter(c => c.contact_type === 'supplier').length}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Search and Filters */}
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <Filter className="h-5 w-5" />
              <span>Filtros</span>
            </CardTitle>
            <CardDescription>
              Filtra contactos por nombre, email, identificación o tipo
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSearch} className="flex gap-4">
              <div className="flex-1">
                <Input
                  type="text"
                  placeholder="Buscar por nombre, email o identificación..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full"
                />
              </div>
              <Select value={contactType} onValueChange={setContactType}>
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Tipo de contacto" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos</SelectItem>
                  <SelectItem value="customer">Clientes</SelectItem>
                  <SelectItem value="supplier">Proveedores</SelectItem>
                </SelectContent>
              </Select>
              <Button type="submit">
                <Search className="mr-2 h-4 w-4" />
                Buscar
              </Button>
              {(search || contactType !== 'all') && (
                <Button type="button" variant="outline" onClick={handleClearFilters}>
                  Limpiar
                </Button>
              )}
            </form>
          </CardContent>
        </Card>

        {/* Contacts Table */}
        <Card>
          <CardHeader>
            <CardTitle>Lista de Contactos</CardTitle>
            <CardDescription>
              {contacts.total === 0
                ? 'No se encontraron contactos.'
                : `Mostrando ${contacts.from} - ${contacts.to} de ${contacts.total} contactos`
              }
            </CardDescription>
          </CardHeader>
          <CardContent>
            {contacts.data.length === 0 ? (
              <div className="text-center py-8">
                <div className="text-gray-500 dark:text-gray-400 mb-4">
                  No se encontraron contactos
                </div>
                <Button asChild>
                  <Link href="/contacts/create">
                    <Plus className="mr-2 h-4 w-4" />
                    Crear primer contacto
                  </Link>
                </Button>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Tipo</TableHead>
                      <TableHead>Nombre</TableHead>
                      <TableHead>Información de Contacto</TableHead>
                      <TableHead>Identificación</TableHead>
                      <TableHead>Dirección</TableHead>
                      <TableHead>Estado</TableHead>
                      <TableHead className="text-right">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {contacts.data.map((contact) => (
                      <TableRow key={contact.id}>
                        <TableCell>
                          <div className="flex items-center space-x-2">
                            {getContactTypeIcon(contact.contact_type)}
                            {getContactTypeBadge(contact.contact_type)}
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="font-medium">{contact.name}</div>
                        </TableCell>
                        <TableCell>
                          <div className="text-sm text-gray-600 dark:text-gray-400">
                            {formatContactInfo(contact)}
                          </div>
                        </TableCell>
                        <TableCell>
                          {contact.identification_object ? (
                            <div className="text-sm">
                              <div className="font-medium">{contact.identification_object.type}</div>
                              <div className="text-gray-600 dark:text-gray-400">{contact.identification_object.number}</div>
                            </div>
                          ) : (
                            <span className="text-gray-400">Sin identificación</span>
                          )}
                        </TableCell>
                        <TableCell>
                          <div className="text-sm text-gray-600 dark:text-gray-400">
                            {formatAddress(contact.primary_address)}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant={contact.status === 'active' ? 'default' : 'secondary'}>
                            {contact.status === 'active' ? 'Activo' : 'Inactivo'}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" size="sm">
                                Acciones
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem asChild>
                                <Link href={`/contacts/${contact.id}`}>
                                  <Eye className="mr-2 h-4 w-4" />
                                  Ver detalles
                                </Link>
                              </DropdownMenuItem>
                              <DropdownMenuItem asChild>
                                <Link href={`/contacts/${contact.id}/edit`}>
                                  <Edit className="mr-2 h-4 w-4" />
                                  Editar
                                </Link>
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onClick={() => handleDelete(contact.id)}
                                className="text-red-600 dark:text-red-400"
                              >
                                <Trash2 className="mr-2 h-4 w-4" />
                                Eliminar
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}

            {/* Pagination */}
            {contacts.last_page > 1 && (
              <div className="flex items-center justify-between mt-6">
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Página {contacts.current_page} de {contacts.last_page}
                </div>
                <div className="flex space-x-2">
                  {contacts.links.prev && (
                    <Button variant="outline" size="sm" asChild>
                      <Link href={contacts.links.prev}>Anterior</Link>
                    </Button>
                  )}
                  {contacts.links.next && (
                    <Button variant="outline" size="sm" asChild>
                      <Link href={contacts.links.next}>Siguiente</Link>
                    </Button>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
