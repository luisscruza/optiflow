import { Building2, Plus } from 'lucide-react';
import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index, store } from '@/actions/App/Http/Controllers/WorkspaceController';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Sucursales',
    href: index().url,
  },
  {
    title: 'Crear Sucursal',
    href: '#',
  },
];

export default function CreateWorkspace() {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Nueva sucursal" />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-6">
          <div className="text-center">
            <div className="mx-auto w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mb-4">
              <Building2 className="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Nueva sucursal
            </h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Registra una nueva sucursal para gestionar las operaciones en diferentes ubicaciones.
            </p>
          </div>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Plus className="h-5 w-5" />
                Información de la Sucursal
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Form
                action={store().url}
                method={store().method}
                options={{
                  preserveScroll: true,
                }}
                className="space-y-6"
              >
                {({ processing, recentlySuccessful, errors }) => (
                  <>
                    <div className="space-y-2">
                      <Label htmlFor="name">
                        Nombre de la Sucursal
                        <span className="text-red-500 ml-1">*</span>
                      </Label>
                      <Input
                        id="name"
                        name="name"
                        type="text"
                        required
                        autoComplete="organization"
                        placeholder="Ej: Sucursal Centro, Sucursal Norte, Oficina Principal"
                        className="mt-1 block w-full"
                      />
                      <InputError message={errors.name} />
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Elige un nombre que identifique claramente la ubicación o zona de la sucursal.
                      </p>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="description">
                        Descripción (Opcional)
                      </Label>
                      <Textarea
                        id="description"
                        name="description"
                        rows={4}
                        placeholder="Describe la ubicación, servicios especiales o características de esta sucursal..."
                        className="mt-1 block w-full"
                      />
                      <InputError message={errors.description} />
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Máximo 1000 caracteres. Incluye información sobre ubicación, horarios o servicios especiales.
                      </p>
                    </div>

                    <div className="flex items-center justify-between pt-6">
                      <Button variant="outline" asChild>
                        <Link href={index().url}>
                          Cancelar
                        </Link>
                      </Button>
                      
                      <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                          {processing ? (
                            <>
                              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                              Creando...
                            </>
                          ) : (
                            <>
                              <Plus className="h-4 w-4 mr-2" />
                              Crear Sucursal
                            </>
                          )}
                        </Button>

                        <Transition
                          show={recentlySuccessful}
                          enter="transition ease-in-out"
                          enterFrom="opacity-0"
                          leave="transition ease-in-out"
                          leaveTo="opacity-0"
                        >
                          <p className="text-sm text-green-600 dark:text-green-400">
                            ¡Sucursal creada exitosamente!
                          </p>
                        </Transition>
                      </div>
                    </div>
                  </>
                )}
              </Form>
            </CardContent>
          </Card>

          <Card className="bg-blue-50 dark:bg-blue-950 border-blue-200 dark:border-blue-800">
            <CardContent className="pt-6">
              <div className="flex items-start gap-3">
                <div className="flex-shrink-0">
                  <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <Building2 className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                  </div>
                </div>
                <div>
                  <h3 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                    ¿Qué es una Sucursal?
                  </h3>
                  <p className="text-sm text-blue-700 dark:text-blue-300 mb-3">
                    Las sucursales te permiten gestionar y organizar las operaciones en diferentes ubicaciones de tu negocio.
                  </p>
                  <ul className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Gestiona inventarios y ventas por ubicación</li>
                    <li>• Asigna personal específico a cada sucursal</li>
                    <li>• Genera reportes independientes por sucursal</li>
                    <li>• Controla accesos y permisos por ubicación</li>
                    <li>• Sincroniza datos entre todas las sucursales</li>
                  </ul>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
