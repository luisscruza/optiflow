import { Transition } from '@headlessui/react';
import { Form, Head, Link, router } from '@inertiajs/react';
import { Building2, Crown, Save, Trash2, Users } from 'lucide-react';
import { useState } from 'react';

import { destroy, index, show, update } from '@/actions/App/Http/Controllers/WorkspaceController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface Workspace {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    owner_id: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    workspace: Workspace;
}

export default function EditWorkspace({ workspace }: Props) {
    const [isDeleting, setIsDeleting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Sucursales',
            href: index().url,
        },
        {
            title: workspace.name,
            href: show(workspace.slug).url,
        },
        {
            title: 'Configuración',
            href: '#',
        },
    ];

    const handleDelete = () => {
        if (
            confirm(
                '¿Estás seguro de que quieres eliminar esta sucursal? Esta acción no se puede deshacer y se eliminarán todos los datos asociados.',
            )
        ) {
            setIsDeleting(true);
            router.delete(destroy(workspace.slug).url, {
                onFinish: () => setIsDeleting(false),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Configuración - ${workspace.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div className="text-center">
                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                            <Building2 className="h-6 w-6 text-primary dark:text-primary" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Configuración de la Sucursal</h1>
                        <p className="mt-2 text-gray-600 dark:text-gray-400">Actualiza la información y configuración de tu sucursal.</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Save className="h-5 w-5" />
                                Información General
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                method="put"
                                action={update(workspace.slug).url}
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
                                                defaultValue={workspace.name}
                                                placeholder="Ej: Sucursal Centro, Sucursal Norte, Oficina Principal"
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="description">Descripción (Opcional)</Label>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                rows={4}
                                                defaultValue={workspace.description || ''}
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
                                                <Link href={show(workspace.slug).url}>Cancelar</Link>
                                            </Button>

                                            <div className="flex items-center gap-4">
                                                <Button type="submit" disabled={processing}>
                                                    {processing ? (
                                                        <>
                                                            <div className="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                                                            Guardando...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Save className="mr-2 h-4 w-4" />
                                                            Guardar Cambios
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
                                                    <p className="text-sm text-green-600 dark:text-green-400">¡Guardado exitosamente!</p>
                                                </Transition>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    <Card className="border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <Users className="h-5 w-5" />
                                Información de la Sucursal
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <p className="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">Creada</p>
                                    <p className="text-gray-900 dark:text-white">
                                        {new Date(workspace.created_at).toLocaleDateString('es-ES', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">Última actualización</p>
                                    <p className="text-gray-900 dark:text-white">
                                        {new Date(workspace.updated_at).toLocaleDateString('es-ES', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </p>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 pt-2 dark:border-gray-700">
                                <div className="flex items-center gap-2">
                                    <Badge variant="secondary" className="bg-blue-100 text-primary dark:bg-blue-900 dark:text-primary">
                                        <Crown className="mr-1 h-3 w-3" />
                                        Administrador
                                    </Badge>
                                    <span className="text-sm text-gray-600 dark:text-gray-400">Tienes control total sobre esta sucursal</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-red-200 dark:border-red-800">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-red-700 dark:text-red-400">
                                <Trash2 className="h-5 w-5" />
                                Zona Peligrosa
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                <div>
                                    <h3 className="mb-2 font-semibold text-red-900 dark:text-red-100">Eliminar Espacio de Trabajo</h3>
                                    <p className="mb-4 text-sm text-red-700 dark:text-red-300">
                                        Una vez que elimines este espacio de trabajo, no hay vuelta atrás. Por favor, asegúrate de que realmente
                                        quieres hacer esto.
                                    </p>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                        disabled={isDeleting}
                                        className="bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-800"
                                    >
                                        {isDeleting ? (
                                            <>
                                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                                                Eliminando...
                                            </>
                                        ) : (
                                            <>
                                                <Trash2 className="mr-2 h-4 w-4" />
                                                Eliminar Espacio de Trabajo
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-blue-200 bg-foreground dark:border-blue-800 dark:bg-blue-950">
                        <CardContent className="pt-6">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                        <Building2 className="h-4 w-4 text-primary dark:text-primary" />
                                    </div>
                                </div>
                                <div>
                                    <h3 className="mb-2 font-semibold text-primary dark:text-primary">Consejos para tu Espacio de Trabajo</h3>
                                    <ul className="space-y-1 text-sm text-primary dark:text-primary">
                                        <li>• Usa un nombre descriptivo que identifique claramente el propósito</li>
                                        <li>• Una buena descripción ayuda a los miembros a entender el contexto</li>
                                        <li>• Revisa periódicamente la configuración para mantenerla actualizada</li>
                                        <li>• Los cambios se aplican inmediatamente a todos los miembros</li>
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
