import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, LayoutGrid } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Procesos',
        href: '/workflows',
    },
    {
        title: 'Crear Flujo',
        href: '/workflows/create',
    },
];

export default function WorkflowCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear Flujo de Trabajo" />

            <div className="max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link href="/workflows">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <LayoutGrid className="h-5 w-5" />
                            Nuevo Flujo de Trabajo
                        </CardTitle>
                        <CardDescription>
                            Crea un nuevo flujo de trabajo para gestionar tus procesos. Se crearán etapas iniciales automáticamente.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Form action="/workflows" method="post">
                            {({ errors, processing }) => (
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre del Flujo</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="Ej: Proceso de Lentes"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                    </div>

                                    <div className="flex justify-end gap-4">
                                        <Link href="/workflows">
                                            <Button type="button" variant="outline">
                                                Cancelar
                                            </Button>
                                        </Link>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Creando...' : 'Crear Flujo de Trabajo'}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
