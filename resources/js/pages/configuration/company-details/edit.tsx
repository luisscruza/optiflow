import { Head, router, Form } from '@inertiajs/react';
import { ArrowLeft, Building2, Save } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        title: 'Datos de la Empresa',
        href: '/company-details',
    },
];

interface Props {
    companyDetails: Record<string, string>;
}

export default function CompanyDetailsEdit({ companyDetails }: Props) {
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string>(companyDetails.logo || '');

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setLogoFile(file);
            const previewUrl = URL.createObjectURL(file);
            setLogoPreview(previewUrl);
        }
    };

    const removeLogo = () => {
        setLogoFile(null);
        setLogoPreview('');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Datos de la Empresa" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Datos de la Empresa</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Configura la información básica de tu empresa que aparecerá en documentos y facturas.
                        </p>
                    </div>

                    <Button variant="outline" onClick={() => router.visit('/configuration')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>

                <Form action="/company-details" method="patch">
                    {({ errors, processing, wasSuccessful }) => (
                        <>
                            <div className="space-y-6">
                                {/* Basic Information */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <Building2 className="h-5 w-5" />
                                            Información Básica
                                        </CardTitle>
                                        <CardDescription>
                                            Información fundamental de tu empresa que aparecerá en documentos oficiales.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="company_name">Nombre de la Empresa *</Label>
                                                <Input
                                                    id="company_name"
                                                    name="company_name"
                                                    defaultValue={companyDetails.company_name}
                                                    placeholder="Centro Óptico Visión Integral"
                                                    required
                                                    className={errors.company_name ? 'border-red-500' : ''}
                                                />
                                                {errors.company_name && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.company_name}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="tax_id">RNC / Cédula</Label>
                                                <Input
                                                    id="tax_id"
                                                    name="tax_id"
                                                    defaultValue={companyDetails.tax_id}
                                                    placeholder="130382573"
                                                    className={errors.tax_id ? 'border-red-500' : ''}
                                                />
                                                {errors.tax_id && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.tax_id}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="address">Dirección</Label>
                                            <Textarea
                                                id="address"
                                                name="address"
                                                defaultValue={companyDetails.address}
                                                placeholder="Calle Principal #123, Sector Centro, Santo Domingo"
                                                rows={3}
                                                className={errors.address ? 'border-red-500' : ''}
                                            />
                                            {errors.address && (
                                                <p className="text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Contact Information */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Información de Contacto</CardTitle>
                                        <CardDescription>
                                            Datos de contacto que aparecerán en facturas y comunicaciones oficiales.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="phone">Teléfono</Label>
                                                <Input
                                                    id="phone"
                                                    name="phone"
                                                    type="tel"
                                                    defaultValue={companyDetails.phone}
                                                    placeholder="+1 809 555 0123"
                                                    className={errors.phone ? 'border-red-500' : ''}
                                                />
                                                {errors.phone && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email</Label>
                                                <Input
                                                    id="email"
                                                    name="email"
                                                    type="email"
                                                    defaultValue={companyDetails.email}
                                                    placeholder="info@empresa.com"
                                                    className={errors.email ? 'border-red-500' : ''}
                                                />
                                                {errors.email && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="currency">Moneda</Label>
                                            <Input
                                                id="currency"
                                                name="currency"
                                                defaultValue={companyDetails.currency}
                                                placeholder="DOP"
                                                className={errors.currency ? 'border-red-500' : ''}
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                Código de moneda (ej: DOP, USD, EUR)
                                            </p>
                                            {errors.currency && (
                                                <p className="text-sm text-red-600 dark:text-red-400">{errors.currency}</p>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Logo Upload */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Logotipo</CardTitle>
                                        <CardDescription>
                                            Sube el logotipo de tu empresa que aparecerá en documentos y facturas.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-6">
                                        <div className="space-y-4">
                                            {/* Current Logo Display */}
                                            {logoPreview && (
                                                <div className="flex items-center gap-4">
                                                    <div className="relative">
                                                        <img
                                                            src={logoPreview}
                                                            alt="Company Logo"
                                                            className="h-20 w-20 rounded-lg border object-contain"
                                                        />
                                                    </div>
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium">Logotipo actual</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {logoFile ? logoFile.name : 'Logotipo guardado'}
                                                        </p>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={removeLogo}
                                                            className="mt-2"
                                                        >
                                                            Remover
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Logo Upload Input */}
                                            <div className="space-y-2">
                                                <Label htmlFor="logo">
                                                    {logoPreview ? 'Cambiar Logotipo' : 'Subir Logotipo'}
                                                </Label>
                                                <Input
                                                    id="logo"
                                                    name="logo"
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={handleLogoChange}
                                                    className={errors.logo ? 'border-red-500' : ''}
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Formatos soportados: PNG, JPG, JPEG. Tamaño máximo: 2MB
                                                </p>
                                                {errors.logo && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.logo}</p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Success Message */}
                            {wasSuccessful && (
                                <div className="my-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                    <p className="text-sm font-medium text-green-800 dark:text-green-200">
                                        ¡Datos de la empresa actualizados correctamente!
                                    </p>
                                </div>
                            )}

                            {/* Submit Button */}
                            <div className="flex items-center justify-end space-x-4 pt-6">
                                <Button type="button" variant="outline" onClick={() => router.visit('/configuration')} disabled={processing}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? (
                                        <>Guardando...</>
                                    ) : (
                                        <>
                                            <Save className="mr-2 h-4 w-4" />
                                            Guardar Cambios
                                        </>
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}