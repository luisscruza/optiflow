import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, MapPin } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Contact } from '@/types';

interface Props {
  supplier: Contact;
  identificationTypes: Record<string, string>;
}

export default function SuppliersEdit({ supplier, identificationTypes }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Proveedores',
      href: '/suppliers',
    },
    {
      title: supplier.name,
      href: `/suppliers/${supplier.id}`,
    },
    {
      title: 'Editar',
      href: `/suppliers/${supplier.id}/edit`,
    },
  ];

  const { data, setData, put, processing, errors } = useForm({
    name: supplier.name || '',
    identification_type: supplier.identification_type || '',
    identification_number: supplier.identification_number || '',
    email: supplier.email || '',
    phone_primary: supplier.phone_primary || '',
    phone_secondary: supplier.phone_secondary || '',
    mobile: supplier.mobile || '',
    fax: supplier.fax || '',
    status: supplier.status as 'active' | 'inactive',
    observations: supplier.observations || '',
    credit_limit: supplier.credit_limit?.toString() || '0',
    address: {
      province: supplier.primary_address?.province || '',
      municipality: supplier.primary_address?.municipality || '',
      country: supplier.primary_address?.country || '',
      description: supplier.primary_address?.description || '',
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(`/suppliers/${supplier.id}`);
  };

  const identificationOptions = Object.entries(identificationTypes).map(([value, label]) => ({
    value,
    label,
  }));

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Editar Proveedor - ${supplier.name}`} />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Editar Proveedor
            </h1>
            <p className="text-gray-600 dark:text-gray-400">
              Actualiza la información del proveedor
            </p>
          </div>

          <div className="flex space-x-3">
            <Button variant="outline" asChild>
              <Link href={`/suppliers/${supplier.id}`}>
                Ver Detalles
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

        <form onSubmit={handleSubmit} className="space-y-8">
          {/* Basic Information */}
          <Card>
            <CardHeader>
              <CardTitle>Información Básica</CardTitle>
              <CardDescription>
                Datos principales del proveedor
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="name" className="required">Nombre</Label>
                  <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Nombre del proveedor"
                    className={errors.name ? 'border-red-500' : ''}
                  />
                  {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="status">Estado</Label>
                  <Select value={data.status} onValueChange={(value: 'active' | 'inactive') => setData('status', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Seleccionar estado" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">Activo</SelectItem>
                      <SelectItem value="inactive">Inactivo</SelectItem>
                    </SelectContent>
                  </Select>
                  {errors.status && <p className="text-sm text-red-500">{errors.status}</p>}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="identification_type">Tipo de Identificación</Label>
                  <Select value={data.identification_type} onValueChange={(value) => setData('identification_type', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Seleccionar tipo" />
                    </SelectTrigger>
                    <SelectContent>
                      {identificationOptions.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.identification_type && <p className="text-sm text-red-500">{errors.identification_type}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="identification_number">Número de Identificación</Label>
                  <Input
                    id="identification_number"
                    type="text"
                    value={data.identification_number}
                    onChange={(e) => setData('identification_number', e.target.value)}
                    placeholder="Número de identificación"
                    className={errors.identification_number ? 'border-red-500' : ''}
                  />
                  {errors.identification_number && <p className="text-sm text-red-500">{errors.identification_number}</p>}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Contact Information */}
          <Card>
            <CardHeader>
              <CardTitle>Información de Contacto</CardTitle>
              <CardDescription>
                Datos de contacto del proveedor
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="email@ejemplo.com"
                    className={errors.email ? 'border-red-500' : ''}
                  />
                  {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="phone_primary">Teléfono Principal</Label>
                  <Input
                    id="phone_primary"
                    type="tel"
                    value={data.phone_primary}
                    onChange={(e) => setData('phone_primary', e.target.value)}
                    placeholder="(809) 123-4567"
                    className={errors.phone_primary ? 'border-red-500' : ''}
                  />
                  {errors.phone_primary && <p className="text-sm text-red-500">{errors.phone_primary}</p>}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="phone_secondary">Teléfono Secundario</Label>
                  <Input
                    id="phone_secondary"
                    type="tel"
                    value={data.phone_secondary}
                    onChange={(e) => setData('phone_secondary', e.target.value)}
                    placeholder="(809) 123-4567"
                    className={errors.phone_secondary ? 'border-red-500' : ''}
                  />
                  {errors.phone_secondary && <p className="text-sm text-red-500">{errors.phone_secondary}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="mobile">Móvil</Label>
                  <Input
                    id="mobile"
                    type="tel"
                    value={data.mobile}
                    onChange={(e) => setData('mobile', e.target.value)}
                    placeholder="(849) 123-4567"
                    className={errors.mobile ? 'border-red-500' : ''}
                  />
                  {errors.mobile && <p className="text-sm text-red-500">{errors.mobile}</p>}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="fax">Fax</Label>
                <Input
                  id="fax"
                  type="tel"
                  value={data.fax}
                  onChange={(e) => setData('fax', e.target.value)}
                  placeholder="(809) 123-4567"
                  className={errors.fax ? 'border-red-500' : ''}
                />
                {errors.fax && <p className="text-sm text-red-500">{errors.fax}</p>}
              </div>
            </CardContent>
          </Card>

          {/* Address Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <MapPin className="h-5 w-5" />
                <span>Dirección</span>
              </CardTitle>
              <CardDescription>
                Dirección principal del proveedor
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="address.province">Provincia</Label>
                  <Input
                    id="address.province"
                    type="text"
                    value={data.address.province}
                    onChange={(e) => setData('address', { ...data.address, province: e.target.value })}
                    placeholder="Provincia"
                    className={errors['address.province'] ? 'border-red-500' : ''}
                  />
                  {errors['address.province'] && <p className="text-sm text-red-500">{errors['address.province']}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="address.municipality">Municipio</Label>
                  <Input
                    id="address.municipality"
                    type="text"
                    value={data.address.municipality}
                    onChange={(e) => setData('address', { ...data.address, municipality: e.target.value })}
                    placeholder="Municipio"
                    className={errors['address.municipality'] ? 'border-red-500' : ''}
                  />
                  {errors['address.municipality'] && <p className="text-sm text-red-500">{errors['address.municipality']}</p>}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="address.country">País</Label>
                <Input
                  id="address.country"
                  type="text"
                  value={data.address.country}
                  onChange={(e) => setData('address', { ...data.address, country: e.target.value })}
                  placeholder="República Dominicana"
                  className={errors['address.country'] ? 'border-red-500' : ''}
                />
                {errors['address.country'] && <p className="text-sm text-red-500">{errors['address.country']}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="address.description">Dirección Detallada</Label>
                <Textarea
                  id="address.description"
                  value={data.address.description}
                  onChange={(e) => setData('address', { ...data.address, description: e.target.value })}
                  placeholder="Calle, número, sector, etc."
                  rows={3}
                  className={errors['address.description'] ? 'border-red-500' : ''}
                />
                {errors['address.description'] && <p className="text-sm text-red-500">{errors['address.description']}</p>}
              </div>
            </CardContent>
          </Card>

          {/* Additional Information */}
          <Card>
            <CardHeader>
              <CardTitle>Información Adicional</CardTitle>
              <CardDescription>
                Configuraciones adicionales del proveedor
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="credit_limit">Límite de Crédito</Label>
                <Input
                  id="credit_limit"
                  type="number"
                  value={data.credit_limit}
                  onChange={(e) => setData('credit_limit', e.target.value)}
                  placeholder="0.00"
                  step="0.01"
                  min="0"
                  className={errors.credit_limit ? 'border-red-500' : ''}
                />
                {errors.credit_limit && <p className="text-sm text-red-500">{errors.credit_limit}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="observations">Observaciones</Label>
                <Textarea
                  id="observations"
                  value={data.observations}
                  onChange={(e) => setData('observations', e.target.value)}
                  placeholder="Notas adicionales sobre el proveedor"
                  rows={3}
                  className={errors.observations ? 'border-red-500' : ''}
                />
                {errors.observations && <p className="text-sm text-red-500">{errors.observations}</p>}
              </div>
            </CardContent>
          </Card>

          {/* Submit */}
          <div className="flex justify-end space-x-4">
            <Button type="button" variant="outline" asChild>
              <Link href={`/suppliers/${supplier.id}`}>Cancelar</Link>
            </Button>
            <Button type="submit" disabled={processing}>
              {processing && <Save className="mr-2 h-4 w-4 animate-spin" />}
              Actualizar Proveedor
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}