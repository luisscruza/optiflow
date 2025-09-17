import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Tax } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Impuestos',
        href: '/taxes',
    },
    {
        title: 'Editar Impuesto',
        href: '#',
    },
];

interface Props {
    tax: Tax;
}

export default function TaxesEdit({ tax }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: tax.name,
        rate: tax.rate.toString(),
        is_default: tax.is_default,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/taxes/${tax.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${tax.name}`} />
            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Editar Impuesto: {tax.name}</h1>
                        <p className="text-gray-600 dark:text-gray-400">Modifica los detalles del impuesto</p>
                    </div>
                </div>

                <Button variant="outline" asChild>
                    <Link href="/taxes">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Link>
                </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Información del Impuesto</CardTitle>
                        <CardDescription>Actualiza los detalles del impuesto</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre del Impuesto *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    placeholder="ej. IVA, ISR, etc."
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className={errors.name ? 'border-red-500' : ''}
                                />
                                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="rate">Tasa (%) *</Label>
                                <Input
                                    id="rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="ej. 16.00"
                                    value={data.rate}
                                    onChange={(e) => setData('rate', e.target.value)}
                                    className={errors.rate ? 'border-red-500' : ''}
                                />
                                {errors.rate && <p className="text-sm text-red-500">{errors.rate}</p>}
                                <p className="text-sm text-gray-500">Ingresa la tasa de impuesto como porcentaje (0-100)</p>
                            </div>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="is_default"
                                checked={data.is_default}
                                onCheckedChange={(checked) => setData('is_default', checked as boolean)}
                            />
                            <Label htmlFor="is_default">Establecer como impuesto predeterminado</Label>
                        </div>
                        {data.is_default && (
                            <p className="rounded-md bg-amber-50 p-3 text-sm text-amber-600 dark:bg-amber-900/20">
                                Este impuesto se aplicará automáticamente a los nuevos productos
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end space-x-4">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/taxes">Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Guardando...' : 'Guardar Cambios'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
