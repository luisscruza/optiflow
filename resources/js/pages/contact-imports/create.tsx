import { ArrowLeft, Upload } from 'lucide-react';
import { ChangeEvent, useState } from 'react';

import { index as contactsIndex } from '@/actions/App/Http/Controllers/ContactController';
import { store } from '@/actions/App/Http/Controllers/ContactImportController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contactos',
        href: contactsIndex().url,
    },
    {
        title: 'Importar contactos',
        href: '',
    },
];

export default function ContactImportsCreate() {
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleFileSelect = (event: ChangeEvent<HTMLInputElement>) => {
        const files = event.target.files ? Array.from(event.target.files) : [];
        setError(null);

        if (files.length === 0) {
            setSelectedFiles([]);
            return;
        }

        const validTypes = ['text/csv', 'application/vnd.ms-excel'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        for (const file of files) {
            if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
                setError('Por favor seleccione archivos CSV (.csv)');
                setSelectedFiles([]);
                return;
            }

            if (file.size > maxSize) {
                setError('Uno de los archivos es demasiado grande. El tamaño máximo es 10MB.');
                setSelectedFiles([]);
                return;
            }
        }

        setSelectedFiles(files);
    };

    const handleUpload = () => {
        if (selectedFiles.length === 0) return;

        setUploading(true);
        setError(null);

        const formData = new FormData();
        if (selectedFiles.length === 1) {
            formData.append('file', selectedFiles[0]);
        } else {
            selectedFiles.forEach((file) => {
                formData.append('files[]', file);
            });
        }

        router.post(store().url, formData, {
            preserveState: true,
            onError: (errors) => {
                setError(errors.file || errors.files || errors['files.0'] || 'Error al cargar el archivo');
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
            <Head title="Nuevaimportación de contactos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={contactsIndex().url}>
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Link>
                        </Button>

                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Nueva importación</h1>
                            <p className="text-muted-foreground">Suba uno o varios archivos CSV para importar contactos de manera masiva</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cargar archivo</CardTitle>
                                    <CardDescription>
                                        Seleccione uno o varios archivos CSV (.csv) que contengan los datos de los contactos
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="file">Archivo CSV</Label>
                                        <Input
                                            id="file"
                                            type="file"
                                            accept=".csv"
                                            multiple
                                            onChange={handleFileSelect}
                                            disabled={uploading}
                                            className="cursor-pointer"
                                        />
                                        {error && <p className="text-sm text-red-600">{error}</p>}
                                        <p className="text-xs text-muted-foreground">
                                            Si sube varios archivos, solo el primero debe incluir encabezados.
                                        </p>
                                    </div>

                                    {selectedFiles.length > 0 && (
                                        <div className="rounded-lg bg-muted p-4">
                                            <div className="flex items-center gap-3">
                                                <Upload className="h-8 w-8 text-muted-foreground" />
                                                <div className="flex-1 space-y-1">
                                                    {selectedFiles.map((file) => (
                                                        <div key={file.name} className="flex items-center justify-between text-sm">
                                                            <span className="font-medium text-foreground">{file.name}</span>
                                                            <span className="text-muted-foreground">{formatFileSize(file.size)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex gap-3">
                                        <Button onClick={handleUpload} disabled={selectedFiles.length === 0 || uploading} className="flex-1">
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
                                    <CardDescription>Su archivo CSV debe tener las siguientes columnas</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3 text-sm">
                                        <div className="flex justify-between">
                                            <span className="font-medium text-red-600">Nombre *</span>
                                            <span className="text-muted-foreground">Obligatorio</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Email</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Teléfono principal</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Móvil</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Tipo de identificación</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Número de identificación</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Estado</span>
                                            <span className="text-muted-foreground">Activo/Inactivo</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="font-medium">Límite de crédito</span>
                                            <span className="text-muted-foreground">Opcional</span>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            El tipo de contacto se asigna automáticamente como Cliente.
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
                                                <p className="text-muted-foreground">Suba su archivo CSV</p>
                                            </div>
                                        </div>
                                        <div className="flex gap-3">
                                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                                2
                                            </div>
                                            <div>
                                                <p className="font-medium">Mapear Columnas</p>
                                                <p className="text-muted-foreground">Relacione las columnas con los campos</p>
                                            </div>
                                        </div>
                                        <div className="flex gap-3">
                                            <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                                3
                                            </div>
                                            <div>
                                                <p className="font-medium">Revisar e Importar</p>
                                                <p className="text-muted-foreground">Procese laimportación de contactos</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
