import { Head, router, useForm } from '@inertiajs/react';
import { Calendar, Hash, Save, Type } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

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
        title: 'Nueva numeración',
        href: '/document-subtypes/create',
    },
];

interface FormData {
    name: string;
    prefix: string;
    start_number: number;
    end_number: number | null;
    valid_until_date: string;
    is_default: boolean;
}

interface Props {
    documentTypes: Array<{
        id: string;
        name: string;
    }>;
}

export default function CreateDocumentSubtype({ documentTypes }: Props) {
    const [documentType] = useState('Factura de venta'); // Default to invoice for now

    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        prefix: '',
        start_number: 1,
        end_number: null,
        valid_until_date: '',
        is_default: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitData = {
            ...data,
            document_type: documentType,
        };

        post('/document-subtypes', {
            onSuccess: () => {
                router.visit('/document-subtypes');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva numeración de comprobantes" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-5 flex items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Nueva numeración</h1>
                        <p className="text-sm text-gray-600">Crea una nueva numeración para los comprobantes de tu negocio.</p>
                    </div>
                </div>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Type className="h-5 w-5" />
                            Información de la numeración
                        </CardTitle>
                        <CardDescription>Completa los datos requeridos para crear una nueva numeración de comprobantes.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Document Type */}
                            <div className="space-y-2">
                                <Label htmlFor="document_type">Tipo de documento</Label>
                                <Select value={documentType} disabled>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Factura de venta">Factura de venta</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-gray-500">Por ahora solo se admiten facturas de venta.</p>
                            </div>

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

                            {/* Prefix */}
                            <div className="space-y-2">
                                <Label htmlFor="prefix">Prefijo *</Label>
                                <div className="relative">
                                    <Hash className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        id="prefix"
                                        type="text"
                                        value={data.prefix}
                                        onChange={(e) => setData('prefix', e.target.value.toUpperCase())}
                                        placeholder="Ej: B01"
                                        className={`pl-10 ${errors.prefix ? 'border-red-500' : ''}`}
                                        maxLength={3}
                                    />
                                </div>
                                {errors.prefix && <p className="text-sm text-red-600">{errors.prefix}</p>}
                                <p className="text-xs text-gray-500">Código de 3 caracteres que identifica la secuencia (ej: B01, B02, E01).</p>
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

                            {/* Valid Until Date */}
                            <div className="space-y-2">
                                <Label htmlFor="valid_until_date">Fecha de vencimiento</Label>
                                <div className="relative">
                                    <Calendar className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        id="valid_until_date"
                                        type="date"
                                        value={data.valid_until_date}
                                        onChange={(e) => setData('valid_until_date', e.target.value)}
                                        className={`pl-10 ${errors.valid_until_date ? 'border-red-500' : ''}`}
                                    />
                                </div>
                                {errors.valid_until_date && <p className="text-sm text-red-600">{errors.valid_until_date}</p>}
                                <p className="text-xs text-gray-500">Fecha hasta la cual esta numeración será válida según la DGII.</p>
                            </div>

                            {/* Is Default */}
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_default"
                                    checked={data.is_default}
                                    onCheckedChange={(checked) => setData('is_default', checked as boolean)}
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="is_default" className="text-sm leading-none font-medium">
                                        Establecer como numeración preferida
                                    </Label>
                                    <p className="text-xs text-gray-500">Esta numeración se utilizará por defecto al crear nuevos documentos.</p>
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex justify-end gap-3 pt-6">
                                <Button type="button" variant="outline" asChild>
                                    <a href="/document-subtypes">Cancelar</a>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Guardando...' : 'Guardar numeración'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
