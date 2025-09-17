import { Head, Link } from '@inertiajs/react';
import { Calculator, Edit, FileText, Package, Star } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Tax } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Impuestos',
        href: '/taxes',
    },
    {
        title: 'Detalles del Impuesto',
        href: '#',
    },
];

interface Props {
    tax: Tax;
}

export default function TaxesShow({ tax }: Props) {
    const formatPercentage = (rate: number) => {
        return `${Number(rate).toLocaleString()}%`;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Impuesto: ${tax.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <div className="flex items-center space-x-3">
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{tax.name}</h1>
                            {tax.is_default && <Star className="h-6 w-6 fill-yellow-500 text-yellow-500" />}
                        </div>
                        <p className="text-gray-600 dark:text-gray-400">Detalles del impuesto y su configuración</p>
                    </div>

                    <div className="flex items-center space-x-4">
                        <Button asChild>
                            <Link href={`/taxes/${tax.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    {/* Main Information */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Calculator className="mr-2 h-5 w-5" />
                                    Información del Impuesto
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                                        <p className="text-lg font-semibold text-gray-900 dark:text-white">{tax.name}</p>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Tasa de Impuesto</label>
                                        <p className="font-mono text-lg font-semibold text-gray-900 dark:text-white">{formatPercentage(tax.rate)}</p>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</label>
                                        <div className="mt-1">
                                            {tax.is_default ? (
                                                <Badge variant="default">Predeterminado</Badge>
                                            ) : (
                                                <Badge variant="secondary">Activo</Badge>
                                            )}
                                        </div>
                                    </div>

                                    <div>
                                        <label className="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</label>
                                        <p className="text-sm text-gray-900 dark:text-white">{formatDate(tax.created_at)}</p>
                                    </div>
                                </div>

                                {tax.is_default && (
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                        <div className="flex items-start space-x-3">
                                            <Star className="mt-0.5 h-5 w-5 text-blue-600" />
                                            <div>
                                                <h4 className="text-sm font-medium text-blue-900 dark:text-blue-100">Impuesto Predeterminado</h4>
                                                <p className="text-sm text-blue-700 dark:text-blue-200">
                                                    Este impuesto se aplicará automáticamente a todos los nuevos productos que se creen.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Statistics */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Estadísticas de Uso</CardTitle>
                                <CardDescription>Resumen del uso de este impuesto</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <div className="flex items-center space-x-3">
                                        <Package className="h-5 w-5 text-blue-600" />
                                        <div>
                                            <p className="text-sm font-medium">Productos</p>
                                            <p className="text-xs text-gray-500">Usando este impuesto</p>
                                        </div>
                                    </div>
                                    <span className="text-lg font-bold text-blue-600">{tax.products_count || 0}</span>
                                </div>

                                <div className="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <div className="flex items-center space-x-3">
                                        <FileText className="h-5 w-5 text-green-600" />
                                        <div>
                                            <p className="text-sm font-medium">Elementos de Documento</p>
                                            <p className="text-xs text-gray-500">En facturas/cotizaciones</p>
                                        </div>
                                    </div>
                                    <span className="text-lg font-bold text-green-600">{tax.document_items_count || 0}</span>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones Rápidas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <Button variant="outline" className="w-full justify-start" asChild>
                                    <Link href={`/taxes/${tax.id}/edit`}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Editar Impuesto
                                    </Link>
                                </Button>

                                <Button variant="outline" className="w-full justify-start" asChild>
                                    <Link href={`/products?tax_id=${tax.id}`}>
                                        <Package className="mr-2 h-4 w-4" />
                                        Ver productos con este Impuesto
                                    </Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
