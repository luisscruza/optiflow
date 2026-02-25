import axios from 'axios';
import { ArrowLeft, Upload } from 'lucide-react';
import { ChangeEvent, useEffect, useState } from 'react';

import { index as invoicesIndex } from '@/actions/App/Http/Controllers/InvoiceController';
import UploadInvoiceImportController from '@/actions/App/Http/Controllers/UploadInvoiceImportController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturas',
        href: invoicesIndex().url,
    },
    {
        title: 'Importar facturas',
        href: '',
    },
];

type UploadResponse = {
    file_path: string;
    absolute_path: string;
    filename: string;
    original_filename: string;
};

type RunResponse = {
    import: InvoiceImport;
};

type StatusResponse = {
    import: InvoiceImport;
};

type InvoiceImport = {
    id: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    limit: number;
    offset: number;
    exit_code: number | null;
    output: string | null;
    error_message: string | null;
    started_at: string | null;
    finished_at: string | null;
    total_records: number;
    processed_records: number;
    imported_records: number;
    skipped_records: number;
    error_records: number;
};

export default function InvoiceImportsCreate() {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);
    const [running, setRunning] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [uploaded, setUploaded] = useState<UploadResponse | null>(null);
    const [runOutput, setRunOutput] = useState<string | null>(null);
    const [importState, setImportState] = useState<InvoiceImport | null>(null);
    const [limit, setLimit] = useState(50);
    const [offset, setOffset] = useState(0);

    const normalizedStatus = importState?.status ?? 'pending';
    const isProcessing = importState ? normalizedStatus === 'pending' || normalizedStatus === 'processing' : false;
    const totalRecords = importState?.total_records ?? 0;
    const processedRecords = importState?.processed_records ?? 0;
    const progressPercent = totalRecords > 0 ? Math.min(100, Math.round((processedRecords / totalRecords) * 100)) : 0;
    const statusLabel: Record<InvoiceImport['status'], string> = {
        pending: 'En cola',
        processing: 'Procesando',
        completed: 'Completado',
        failed: 'Fallido',
    };

    useEffect(() => {
        if (!importState || !isProcessing) {
            return;
        }

        const interval = window.setInterval(async () => {
            try {
                const response = await axios.get<StatusResponse>(`/invoice-imports/${importState.id}/status`);
                const latestImport = response.data.import;

                setImportState(latestImport);

                if (latestImport.status === 'completed') {
                    setRunOutput(latestImport.output || 'Importacion completada.');
                    setError(null);
                }

                if (latestImport.status === 'failed') {
                    setRunOutput(latestImport.output || null);
                    setError(latestImport.error_message || 'Error al ejecutar la importacion');
                }
            } catch (requestError) {
                if (axios.isAxiosError(requestError)) {
                    const message =
                        requestError.response?.data?.message || requestError.response?.data?.errors?.file_path || 'Error al consultar el estado';
                    setError(message);
                } else {
                    setError('Error al consultar el estado');
                }
            }
        }, 3000);

        return () => window.clearInterval(interval);
    }, [importState, isProcessing]);

    const handleFileSelect = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        setError(null);
        setUploaded(null);
        setRunOutput(null);
        setImportState(null);

        if (!file) {
            setSelectedFile(null);
            return;
        }

        const validTypes = ['text/csv', 'application/vnd.ms-excel'];
        if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
            setError('Por favor seleccione un archivo CSV (.csv)');
            setSelectedFile(null);
            return;
        }

        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            setError('El archivo es demasiado grande. El tamaño máximo es 10MB.');
            setSelectedFile(null);
            return;
        }

        setSelectedFile(file);
    };

    const handleUpload = async () => {
        if (!selectedFile || uploading) return;

        setUploading(true);
        setError(null);
        setUploaded(null);
        setImportState(null);

        const formData = new FormData();
        formData.append('file', selectedFile);

        try {
            const response = await axios.post<UploadResponse>(UploadInvoiceImportController().url, formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            setUploaded(response.data);
            setRunOutput(null);
        } catch (requestError) {
            if (axios.isAxiosError(requestError)) {
                const message = requestError.response?.data?.errors?.file || requestError.response?.data?.message || 'Error al cargar el archivo';
                setError(message);
            } else {
                setError('Error al cargar el archivo');
            }
        } finally {
            setUploading(false);
        }
    };

    const handleRunImport = async () => {
        if (!uploaded || running) return;

        setRunning(true);
        setError(null);
        setRunOutput(null);
        setImportState(null);

        try {
            const response = await axios.post<RunResponse>('/invoice-imports/run', {
                file_path: uploaded.file_path,
                filename: uploaded.filename,
                original_filename: uploaded.original_filename,
                limit,
                offset,
            });

            setImportState(response.data.import);
        } catch (requestError) {
            if (axios.isAxiosError(requestError)) {
                const message =
                    requestError.response?.data?.message || requestError.response?.data?.errors?.file_path || 'Error al ejecutar la importacion';
                setError(message);
            } else {
                setError('Error al ejecutar la importacion');
            }
        } finally {
            setRunning(false);
        }
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
            <Head title="Importar facturas" />

            <div className="space-y-6 px-8">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={invoicesIndex().url}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Link>
                    </Button>

                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Importar facturas</h1>
                        <p className="text-muted-foreground">Suba un archivo CSV para preparar la importación</p>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Cargar archivo</CardTitle>
                                <CardDescription>Seleccione un archivo CSV (.csv) con los datos de las facturas</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="file">Archivo CSV</Label>
                                    <Input
                                        id="file"
                                        type="file"
                                        accept=".csv"
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

                                {uploaded && (
                                    <div className="space-y-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                                        <div>
                                            <p className="font-medium">Archivo listo para importar</p>
                                            <p className="mt-1 text-yellow-700">
                                                Inicia la importación desde aquí. El proceso se ejecuta en segundo plano y podrás ver el avance.
                                            </p>
                                        </div>

                                        <div className="grid gap-3 md:grid-cols-[140px_1fr] md:items-center">
                                            <Label htmlFor="limit">Limite</Label>
                                            <Input
                                                id="limit"
                                                type="number"
                                                min={1}
                                                max={10000}
                                                value={limit}
                                                onChange={(event) => {
                                                    const value = event.target.value;
                                                    setLimit(value === '' ? 1 : Number(value));
                                                }}
                                            />
                                        </div>

                                        <div className="grid gap-3 md:grid-cols-[140px_1fr] md:items-center">
                                            <Label htmlFor="offset">Offset</Label>
                                            <Input
                                                id="offset"
                                                type="number"
                                                min={0}
                                                max={1000000}
                                                value={offset}
                                                onChange={(event) => {
                                                    const value = event.target.value;
                                                    setOffset(value === '' ? 0 : Number(value));
                                                }}
                                            />
                                        </div>

                                        <Button onClick={handleRunImport} disabled={running || isProcessing} className="w-full">
                                            {running ? (
                                                <>
                                                    <Upload className="mr-2 h-4 w-4 animate-spin" />
                                                    Encolando importacion...
                                                </>
                                            ) : isProcessing ? (
                                                <>
                                                    <Upload className="mr-2 h-4 w-4 animate-spin" />
                                                    Importacion en proceso...
                                                </>
                                            ) : (
                                                <>Ejecutar importacion</>
                                            )}
                                        </Button>

                                        {importState && (
                                            <div className="space-y-2 rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                                <div>
                                                    Estado: <span className="font-medium">{statusLabel[normalizedStatus]}</span>
                                                </div>
                                                <div className="text-xs text-slate-600">
                                                    Límite: {importState.limit} · Offset: {importState.offset}
                                                </div>
                                                {totalRecords > 0 && (
                                                    <div className="space-y-1">
                                                        <div className="flex items-center justify-between text-xs text-slate-600">
                                                            <span>
                                                                Procesadas {processedRecords} / {totalRecords}
                                                            </span>
                                                            <span>{progressPercent}%</span>
                                                        </div>
                                                        <div className="h-2 w-full rounded-full bg-slate-200">
                                                            <div
                                                                className="h-2 rounded-full bg-primary transition-all"
                                                                style={{ width: `${progressPercent}%` }}
                                                            />
                                                        </div>
                                                        <div className="grid grid-cols-3 gap-2 text-xs text-slate-600">
                                                            <span>Importadas: {importState.imported_records}</span>
                                                            <span>Omitidas: {importState.skipped_records}</span>
                                                            <span>Errores: {importState.error_records}</span>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {runOutput && (
                                            <div className="rounded-md bg-slate-900/95 p-4 text-xs text-slate-100">
                                                <pre className="font-mono whitespace-pre-wrap">{runOutput}</pre>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Importante</CardTitle>
                                <CardDescription>Esta importacion limpia las facturas existentes</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Al ejecutar el import, se eliminan las facturas y sus items antes de cargar el nuevo archivo. Haga una copia de
                                    seguridad si necesita conservar datos previos.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Formato del Archivo</CardTitle>
                                <CardDescription>El CSV debe tener encabezados y datos completos</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between">
                                        <span className="font-medium text-red-600">DOCUMENT_NUMBER *</span>
                                        <span className="text-muted-foreground">Obligatorio</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">CLIENTE</span>
                                        <span className="text-muted-foreground">Obligatorio</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">FECHA</span>
                                        <span className="text-muted-foreground">Obligatorio</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span className="font-medium">TOTAL - FACTURA</span>
                                        <span className="text-muted-foreground">Obligatorio</span>
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Incluya las columnas de impuestos y productos tal como vienen del export.
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Proceso de Importacion</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3 text-sm">
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                                            1
                                        </div>
                                        <div>
                                            <p className="font-medium">Cargar archivo</p>
                                            <p className="text-muted-foreground">Suba el CSV de facturas</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                            2
                                        </div>
                                        <div>
                                            <p className="font-medium">Obtener ruta o ejecutar</p>
                                            <p className="text-muted-foreground">Use la ruta o ejecute desde aqui</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">
                                            3
                                        </div>
                                        <div>
                                            <p className="font-medium">Ejecutar importacion</p>
                                            <p className="text-muted-foreground">Corra el comando con la ruta</p>
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
