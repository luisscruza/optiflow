import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';

import { index, store } from '@/actions/App/Http/Controllers/WorkspaceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
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

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div className="text-center">
                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                            <Building2 className="h-6 w-6 text-primary dark:text-primary" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Nueva sucursal</h1>
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
                                                <span className="ml-1 text-red-500">*</span>
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
                                            <Label htmlFor="description">Descripción (Opcional)</Label>
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
                                                <Link href={index().url}>Cancelar</Link>
                                            </Button>

                                            <div className="flex items-center gap-4">
                                                <Button type="submit" disabled={processing}>
                                                    {processing ? (
                                                        <>
                                                            <div className="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                                                            Creando...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Plus className="mr-2 h-4 w-4" />
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
                                                    <p className="text-sm text-green-600 dark:text-green-400">¡Sucursal creada exitosamente!</p>
                                                </Transition>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                        <Building2 className="h-4 w-4 text-primary dark:text-primary" />
                                    </div>
                                </div>
                                <div>
                                    <h3 className="mb-2 font-semibold text-primary dark:text-primary">¿Qué es una Sucursal?</h3>
                                    <p className="mb-3 text-sm text-primary dark:text-primary">
                                        Las sucursales te permiten gestionar y organizar las operaciones en diferentes ubicaciones de tu negocio.
                                    </p>
                                    <ul className="space-y-1 text-sm text-primary dark:text-primary">
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
