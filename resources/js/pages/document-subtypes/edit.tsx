import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Save, Type } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    start_number: number;
    end_number: number | null;
    next_number: number;
    valid_until_date: string | null;
    is_default: boolean;
}

interface FormData {
    name: string;
    start_number: number;
    end_number: number | null;
}

interface Props {
    subtype: DocumentSubtype;
}

export default function EditDocumentSubtype({ subtype }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
            {
        title: 'Configuración',
        href: '/configuration',
    },
        {
            title: 'Numeraciones de comprobantes',
            href: '/document-subtypes',
        },
        {
            title: subtype.name,
            href: `/document-subtypes/${subtype.id}`,
        },
        {
            title: 'Editar',
            href: `/document-subtypes/${subtype.id}/edit`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm<FormData>({
        name: subtype.name,
        start_number: subtype.start_number,
        end_number: subtype.end_number,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        patch(`/document-subtypes/${subtype.id}`, {
            onSuccess: () => {
                router.visit(`/document-subtypes/${subtype.id}`);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar numeración: ${subtype.name}`} />

            <div className="max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <a href={`/document-subtypes/${subtype.id}`}>
                            <ArrowLeft className="h-4 w-4" />
                        </a>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Editar numeración</h1>
                        <p className="text-sm text-gray-600">Modifica los campos permitidos de esta numeración.</p>
                    </div>
                </div>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Type className="h-5 w-5" />
                            Campos editables
                        </CardTitle>
                        <CardDescription>Solo puedes modificar el nombre y los números de rango de esta numeración.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ej: Facturas principales"
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>

                            {/* Numbers */}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="start_number">Número inicial *</Label>
                                    <Input
                                        id="start_number"
                                        type="number"
                                        value={data.start_number}
                                        onChange={(e) => setData('start_number', parseInt(e.target.value) || 1)}
                                        min="1"
                                        className={errors.start_number ? 'border-red-500' : ''}
                                    />
                                    {errors.start_number && <p className="text-sm text-red-600">{errors.start_number}</p>}
                                    {data.start_number > subtype.next_number && (
                                        <p className="text-xs text-orange-600">
                                            ⚠️ Al cambiar el número inicial a {data.start_number.toLocaleString()}, el siguiente número se actualizará
                                            automáticamente.
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="end_number">Número final</Label>
                                    <Input
                                        id="end_number"
                                        type="number"
                                        value={data.end_number || ''}
                                        onChange={(e) => setData('end_number', e.target.value ? parseInt(e.target.value) : null)}
                                        min={data.start_number + 1}
                                        placeholder="Opcional"
                                        className={errors.end_number ? 'border-red-500' : ''}
                                    />
                                    {errors.end_number && <p className="text-sm text-red-600">{errors.end_number}</p>}
                                    <p className="text-xs text-gray-500">Dejar vacío para secuencia infinita.</p>
                                </div>
                            </div>

                            {/* Current Status */}
                            <div className="rounded-lg bg-gray-50 p-4">
                                <h4 className="mb-2 font-medium text-gray-900">Estado actual</h4>
                                <div className="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span className="text-gray-500">Siguiente número:</span>
                                        <span className="ml-2 font-medium">{subtype.next_number.toLocaleString()}</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-500">Rango actual:</span>
                                        <span className="ml-2 font-medium">
                                            {subtype.start_number.toLocaleString()} - {subtype.end_number?.toLocaleString() || '∞'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end gap-3 pt-6">
                                <Button type="button" variant="outline" asChild>
                                    <a href={`/document-subtypes/${subtype.id}`}>Cancelar</a>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Guardando...' : 'Guardar cambios'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
