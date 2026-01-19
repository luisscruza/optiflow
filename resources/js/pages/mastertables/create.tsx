import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

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
        title: 'Crear tabla maestra',
        href: '/mastertables/create',
    },
];

interface MastertableFormData {
    name: string;
    alias: string;
    description: string;
}

export default function CreateMastertable() {
    const { data, setData, post, processing, errors } = useForm<MastertableFormData>({
        name: '',
        alias: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/mastertables');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear tabla maestra" />

            <div className="max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Crear tabla maestra</h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Crea una nueva tabla maestra para organizar datos personalizados en tu sistema.
                    </p>
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
                                <Label htmlFor="alias">
                                    Alias <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="alias"
                                    value={data.alias}
                                    onChange={(e) => setData('alias', e.target.value)}
                                    placeholder="Ej: lens_types"
                                    className={errors.alias ? 'border-red-500' : ''}
                                />
                                <p className="text-sm text-gray-500">
                                    Identificador único para la tabla. Usa letras minúsculas, números y guiones bajos.
                                </p>
                                {errors.alias && <p className="text-sm text-red-500">{errors.alias}</p>}
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
                        </CardContent>
                    </Card>

                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Crear tabla maestra'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
