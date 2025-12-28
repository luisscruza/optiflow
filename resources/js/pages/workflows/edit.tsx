import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, LayoutGrid } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Workflow } from '@/types';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    workflow: Workflow;
}

export default function WorkflowEdit({ workflow }: Props) {
    const [isActive, setIsActive] = useState(workflow.is_active);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Procesos',
            href: '/workflows',
        },
        {
            title: workflow.name,
            href: `/workflows/${workflow.id}`,
        },
        {
            title: 'Editar',
            href: `/workflows/${workflow.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${workflow.name}`} />

            <div className="max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link href={`/workflows/${workflow.id}`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver al tablero
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <LayoutGrid className="h-5 w-5" />
                            Editar flujo de trabajo
                        </CardTitle>
                        <CardDescription>Modifica la configuración del flujo de trabajo.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Form action={`/workflows/${workflow.id}`} method="patch">
                            {({ errors, processing }) => (
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre del flujo</Label>
                                        <Input id="name" name="name" defaultValue={workflow.name} required />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                    </div>

                                    <div className="flex items-center justify-between rounded-lg border p-4">
                                        <div className="space-y-0.5">
                                            <Label htmlFor="is_active">Estado del flujo</Label>
                                            <p className="text-sm text-muted-foreground">
                                                {isActive ? 'El flujo está activo y visible' : 'El flujo está inactivo'}
                                            </p>
                                        </div>
                                        <input type="hidden" name="is_active" value={isActive ? '1' : '0'} />
                                        <button
                                            type="button"
                                            role="switch"
                                            aria-checked={isActive}
                                            onClick={() => setIsActive(!isActive)}
                                            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background ${
                                                isActive ? 'bg-primary' : 'bg-input'
                                            }`}
                                        >
                                            <span
                                                className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-background shadow-lg ring-0 transition duration-200 ease-in-out ${
                                                    isActive ? 'translate-x-5' : 'translate-x-0'
                                                }`}
                                            />
                                        </button>
                                    </div>

                                    <div className="flex justify-end gap-4">
                                        <Link href={`/workflows/${workflow.id}`}>
                                            <Button type="button" variant="outline">
                                                Cancelar
                                            </Button>
                                        </Link>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Guardando...' : 'Guardar cambios'}
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
