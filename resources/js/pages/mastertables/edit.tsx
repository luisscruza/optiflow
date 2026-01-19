import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Mastertable } from '@/types';

interface Props {
    mastertable: Mastertable & { items_count: number };
}

interface MastertableFormData {
    name: string;
    description: string;
}

export default function EditMastertable({ mastertable }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Configuración',
            href: '/configuration',
        },
        {
            title: 'Tablas maestras',
            href: '/mastertables',
        },
        {
            title: mastertable.name,
            href: `/mastertables/${mastertable.id}`,
        },
        {
            title: 'Editar',
            href: `/mastertables/${mastertable.id}/edit`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm<MastertableFormData>({
        name: mastertable.name,
        description: mastertable.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/mastertables/${mastertable.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${mastertable.name}`} />

            <div className="max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Editar tabla maestra</h1>
                    <p className="text-gray-600 dark:text-gray-400">Actualiza la información de la tabla maestra.</p>
                </div>

                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Información básica</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">
                                    Nombre <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ej: Tipos de lentes"
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="alias">Alias</Label>
                                <Input id="alias" value={mastertable.alias} disabled className="bg-gray-50 dark:bg-gray-800" />
                                <p className="text-sm text-gray-500">El alias no se puede modificar una vez creado.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Describe el propósito de esta tabla maestra..."
                                    rows={3}
                                    className={errors.description ? 'border-red-500' : ''}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>

                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950">
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    <strong>Nota:</strong> Los elementos de la tabla se gestionan en la{' '}
                                    <a href={`/mastertables/${mastertable.id}`} className="underline">
                                        página de detalles
                                    </a>
                                    .
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
