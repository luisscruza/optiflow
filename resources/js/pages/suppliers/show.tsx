import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, MapPin, Phone, Mail, CreditCard, FileText, Calendar, Building2, Package } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { type BreadcrumbItem, type Contact } from '@/types';

interface Props {
  supplier: Contact;
}

export default function SuppliersShow({ supplier }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Proveedores',
      href: '/suppliers',
    },
    {
      title: supplier.name,
      href: `/suppliers/${supplier.id}`,
    },
  ];

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-DO', {
      style: 'currency',
      currency: 'DOP',
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('es-DO', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(dateString));
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Proveedor - ${supplier.name}`} />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              {supplier.name}
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              Información detallada del proveedor
            </p>
          </div>

          <div className="flex space-x-3">
            <Button asChild>
              <Link href={`/suppliers/${supplier.id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Editar
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href="/suppliers">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Volver
              </Link>
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Information */}
          <div className="lg:col-span-2 space-y-8">
            {/* Basic Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Building2 className="h-5 w-5" />
                  <span>Información Básica</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</dt>
                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.name}</dd>
                  </div>
                  <div>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</dt>
                    <dd className="mt-1">
                      <Badge variant={supplier.status === 'active' ? 'default' : 'secondary'}>
                        {supplier.status === 'active' ? 'Activo' : 'Inactivo'}
                      </Badge>
                    </dd>
                  </div>
                </div>

                {supplier.identification_object && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo de Identificación</dt>
                      <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.identification_object.type}</dd>
                    </div>
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Número de Identificación</dt>
                      <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.identification_object.number}</dd>
                    </div>
                  </div>
                )}

                {supplier.observations && (
                  <div>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Observaciones</dt>
                    <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.observations}</dd>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Contact Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Phone className="h-5 w-5" />
                  <span>Información de Contacto</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {supplier.email && (
                  <div className="flex items-center space-x-3">
                    <Mail className="h-4 w-4 text-gray-400" />
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                      <dd className="text-sm text-gray-900 dark:text-white">
                        <a href={`mailto:${supplier.email}`} className="hover:underline">
                          {supplier.email}
                        </a>
                      </dd>
                    </div>
                  </div>
                )}

                {supplier.phone_primary && (
                  <div className="flex items-center space-x-3">
                    <Phone className="h-4 w-4 text-gray-400" />
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono Principal</dt>
                      <dd className="text-sm text-gray-900 dark:text-white">
                        <a href={`tel:${supplier.phone_primary}`} className="hover:underline">
                          {supplier.phone_primary}
                        </a>
                      </dd>
                    </div>
                  </div>
                )}

                {supplier.phone_secondary && (
                  <div className="flex items-center space-x-3">
                    <Phone className="h-4 w-4 text-gray-400" />
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono Secundario</dt>
                      <dd className="text-sm text-gray-900 dark:text-white">
                        <a href={`tel:${supplier.phone_secondary}`} className="hover:underline">
                          {supplier.phone_secondary}
                        </a>
                      </dd>
                    </div>
                  </div>
                )}

                {supplier.mobile && (
                  <div className="flex items-center space-x-3">
                    <Phone className="h-4 w-4 text-gray-400" />
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Móvil</dt>
                      <dd className="text-sm text-gray-900 dark:text-white">
                        <a href={`tel:${supplier.mobile}`} className="hover:underline">
                          {supplier.mobile}
                        </a>
                      </dd>
                    </div>
                  </div>
                )}

                {supplier.fax && (
                  <div className="flex items-center space-x-3">
                    <Phone className="h-4 w-4 text-gray-400" />
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Fax</dt>
                      <dd className="text-sm text-gray-900 dark:text-white">{supplier.fax}</dd>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Address Information */}
            {supplier.primary_address && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center space-x-2">
                    <MapPin className="h-5 w-5" />
                    <span>Dirección Principal</span>
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {supplier.primary_address.province && (
                      <div>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Provincia</dt>
                        <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.primary_address.province}</dd>
                      </div>
                    )}
                    {supplier.primary_address.municipality && (
                      <div>
                        <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Municipio</dt>
                        <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.primary_address.municipality}</dd>
                      </div>
                    )}
                  </div>

                  {supplier.primary_address.country && (
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">País</dt>
                      <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.primary_address.country}</dd>
                    </div>
                  )}

                  {supplier.primary_address.description && (
                    <div>
                      <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Dirección Detallada</dt>
                      <dd className="mt-1 text-sm text-gray-900 dark:text-white">{supplier.primary_address.description}</dd>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-8">
            {/* Financial Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <CreditCard className="h-5 w-5" />
                  <span>Información Financiera</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Límite de Crédito</dt>
                  <dd className="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                    {formatCurrency(supplier.credit_limit)}
                  </dd>
                </div>

                <Separator />
              </CardContent>
            </Card>

            {/* Activity Statistics */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <FileText className="h-5 w-5" />
                  <span>Estadísticas</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Documentos</dt>
                  <dd className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {supplier.documents_count || 0}
                  </dd>
                </div>

                <Separator />

                <div className="flex items-center space-x-3">
                  <Package className="h-4 w-4 text-gray-400" />
                  <div>
                    <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Productos Suministrados</dt>
                    <dd className="mt-1 text-2xl font-bold text-blue-600">
                      {supplier.supplied_stocks_count || 0}
                    </dd>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Timestamps */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center space-x-2">
                  <Calendar className="h-5 w-5" />
                  <span>Fechas</span>
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Creado</dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                    {formatDate(supplier.created_at)}
                  </dd>
                </div>

                <div>
                  <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">Última Actualización</dt>
                  <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                    {formatDate(supplier.updated_at)}
                  </dd>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}