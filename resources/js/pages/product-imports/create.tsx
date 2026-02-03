import { ArrowLeft, Upload } from 'lucide-react';
import { ChangeEvent, useState } from 'react';

import { index, store } from '@/actions/App/Http/Controllers/ProductImportController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Importación de Productos',
        href: index().url,
    },
    {
        title: 'Nueva importación',
        href: '',
    },
];

export default function ProductImportsCreate() {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleFileSelect = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        setError(null);

        if (!file) {
            setSelectedFile(null);
            return;
        }

        // Validate file type
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!validTypes.includes(file.type)) {
            setError('Por favor seleccione un archivo Excel (.xlsx o .xls)');
            setSelectedFile(null);
            return;
        }

        // Validate file size (10MB limit)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            setError('El archivo es demasiado grande. El tamaño máximo es 10MB.');
            setSelectedFile(null);
            return;
        }

        setSelectedFile(file);
    };

    const handleUpload = () => {
        if (!selectedFile) return;

        setUploading(true);
        setError(null);

        const formData = new FormData();
        formData.append('file', selectedFile);

        router.post(store().url, formData, {
            preserveState: true,
            onSuccess: () => {
                // Redirect will be handled by the server response
            },
            onError: (errors) => {
                setError(errors.file || 'Error al cargar el archivo');
                setUploading(false);
            },
            onFinish: () => {
                if (!error) {
                    setUploading(false);
                }
            },
        });
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva importación de Productos" />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>

                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Nueva importación</h1>
                        <p className="text-muted-foreground">Suba un archivo Excel para importar productos de manera masiva</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cargar archivo</CardTitle>
                                <CardDescription>Seleccione un archivo Excel (.xlsx o .xls) que contenga los datos de los productos</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="file">Archivo Excel</Label>
                                    <Input
                                        id="file"
                                        type="file"
                                        accept=".xlsx,.xls"
                                        onChange={handleFileSelect}
                                        disabled={uploading}
                                        className="cursor-pointer"
                                    />
                                    {error && <p className="text-sm text-red-600">{error}</p>}
                                </div>

                                {selectedFile && (
                                    <div className="rounded-lg bg-muted p-4">
                                        <div className="flex items-center gap-3">
                                            <Upload className="h-8 w-8 text-muted-foreground" />
                                            <div className="flex-1">
                                                <p className="font-medium">{selectedFile.name}</p>
                                                <p className="text-sm text-muted-foreground">{formatFileSize(selectedFile.size)}</p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="flex gap-3">
                                    <Button onClick={handleUpload} disabled={!selectedFile || uploading} className="flex-1">
                                        {uploading ? (
                                            <>
                                                <Upload className="mr-2 h-4 w-4 animate-spin" />
                                                Cargando...
                                            </>
                                        ) : (
                                            <>
                                                <Upload className="mr-2 h-4 w-4" />
                                                Cargar archivo
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Formato del Archivo</CardTitle>
                                <CardDescription>Su archivo Excel debe tener las siguientes columnas</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between">
                                        <span className="font-medium text-red-600">Nombre *</span>
                                        <span className="text-muted-foreground">Obligatorio</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">SKU</span>
                                        <span className="text-muted-foreground">Opcional</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">Descripción</span>
                                        <span className="text-muted-foreground">Opcional</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">Precio</span>
                                        <span className="text-muted-foreground">Opcional</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">Costo</span>
                                        <span className="text-muted-foreground">Opcional</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">Controlar Stock</span>
                                        <span className="text-muted-foreground">Sí/No</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">Permitir Stock Negativo</span>
                                        <span className="text-muted-foreground">Sí/No</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Proceso de Importación</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 text-sm">
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                            1
                                        </div>
                                        <div>
                                            <p className="font-medium">Cargar archivo</p>
                                            <p className="text-muted-foreground">Suba su archivo Excel</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                            2
                                        </div>
                                        <div>
                                            <p className="font-medium">Mapear Columnas</p>
                                            <p className="text-muted-foreground">Relacione las columnas de su archivo con los campos</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                            3
                                        </div>
                                        <div>
                                            <p className="font-medium">Revisar e Importar</p>
                                            <p className="text-muted-foreground">Confirme los datos y procese la importación</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
