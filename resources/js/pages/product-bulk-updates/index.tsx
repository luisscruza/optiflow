import { Download, FileSpreadsheet, Upload } from 'lucide-react';
import { ChangeEvent, useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData, type ProductBulkUpdate } from '@/types';
import { Head, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Actualizacion masiva de productos',
        href: '/product-bulk-updates',
    },
];

interface Props {
    latestBulkUpdate?: ProductBulkUpdate | null;
    bulkUpdates: PaginatedData<ProductBulkUpdate>;
}

export default function ProductBulkUpdatesIndex({ latestBulkUpdate = null, bulkUpdates }: Props) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const latestRun = latestBulkUpdate;
    const latestErrors = useMemo(() => latestRun?.validation_errors?.slice(0, 10) ?? [], [latestRun]);

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        setError(null);

        if (file && !file.name.toLowerCase().endsWith('.csv')) {
            setSelectedFile(null);
            setError('Debe seleccionar un archivo CSV.');

            return;
        }

        setSelectedFile(file);
    };

    const handleDownloadTemplate = () => {
        window.location.href = '/product-bulk-updates/template/download';
    };

    const handleUpload = () => {
        if (!selectedFile) {
            return;
        }

        setUploading(true);
        setError(null);

        router.post(
            '/product-bulk-updates',
            { file: selectedFile },
            {
                forceFormData: true,
                onError: (errors) => {
                    setError((errors.file as string) ?? 'No se pudo procesar el archivo.');
                },
                onFinish: () => setUploading(false),
            },
        );
    };

    const handleConfirm = (bulkUpdateId: number) => {
        router.post(`/product-bulk-updates/${bulkUpdateId}/confirm`);
    };

    const statusVariant = (status: ProductBulkUpdate['status']) => {
        switch (status) {
            case 'completed':
                return 'default';
            case 'failed':
                return 'destructive';
            case 'processing':
                return 'secondary';
            case 'ready':
                return 'outline';
            default:
                return 'outline';
        }
    };

    const statusLabel = (status: ProductBulkUpdate['status']) => {
        switch (status) {
            case 'completed':
                return 'Completado';
            case 'failed':
                return 'Con errores';
            case 'processing':
                return 'Procesando';
            case 'ready':
                return 'Listo para confirmar';
            default:
                return 'Pendiente';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Actualizacion masiva de productos" />

            <div className="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div className="max-w-3xl space-y-2">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Actualizacion masiva de productos</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Descarga la plantilla CSV con el estado actual de tus productos, modifica las columnas editables y vuelve a subirla para
                            revisar los cambios antes de aplicarlos.
                        </p>
                    </div>

                    <Button variant="outline" onClick={handleDownloadTemplate}>
                        <Download className="mr-2 h-4 w-4" />
                        Descargar plantilla CSV
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Subir cambios</CardTitle>
                            <CardDescription>Sube tu CSV, revisa la vista previa y confirma para aplicar los cambios.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Input type="file" accept=".csv" onChange={handleFileChange} disabled={uploading} className="cursor-pointer" />

                            {selectedFile && (
                                <div className="flex items-center gap-3 rounded-lg border bg-muted/30 p-4">
                                    <FileSpreadsheet className="h-8 w-8 text-muted-foreground" />
                                    <div>
                                        <p className="font-medium">{selectedFile.name}</p>
                                        <p className="text-sm text-muted-foreground">Listo para generar la vista previa.</p>
                                    </div>
                                </div>
                            )}

                            {error && <p className="text-sm text-red-600">{error}</p>}

                            <div className="flex gap-3">
                                <Button onClick={handleUpload} disabled={!selectedFile || uploading}>
                                    <Upload className={`mr-2 h-4 w-4 ${uploading ? 'animate-pulse' : ''}`} />
                                    {uploading ? 'Generando vista previa...' : 'Subir CSV'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Reglas importantes</CardTitle>
                            <CardDescription>Esta herramienta solo actualiza productos existentes.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm text-muted-foreground">
                            <p>`SKU` identifica el producto. Si no existe, la fila queda con error y no crea el producto.</p>
                            <p>`STATUS` acepta `active` o `inactive`.</p>
                            <p>Cada columna `WORKSPACE_*_STOCK` representa la existencia final absoluta de esa sucursal.</p>
                            <p>Las celdas de stock en blanco no cambian la existencia actual.</p>
                        </CardContent>
                    </Card>
                </div>

                {latestRun && (
                    <div className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                        <Card>
                            <CardHeader>
                                <CardTitle>Ultima carga</CardTitle>
                                <CardDescription>{latestRun.original_filename}</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Badge variant={statusVariant(latestRun.status)}>{statusLabel(latestRun.status)}</Badge>
                                    <span className="text-sm text-muted-foreground">{latestRun.total_rows} filas en el archivo</span>
                                </div>

                                {latestRun.summary && (
                                    <div className="grid grid-cols-2 gap-3 text-sm">
                                        <div className="rounded-lg border p-3">
                                            <p className="text-muted-foreground">Productos a actualizar</p>
                                            <p className="text-2xl font-semibold">{latestRun.summary.products_updated}</p>
                                        </div>
                                        <div className="rounded-lg border p-3">
                                            <p className="text-muted-foreground">Sin cambios</p>
                                            <p className="text-2xl font-semibold">{latestRun.summary.unchanged_rows}</p>
                                        </div>
                                        <div className="rounded-lg border p-3">
                                            <p className="text-muted-foreground">Ajustes de stock</p>
                                            <p className="text-2xl font-semibold">{latestRun.summary.stock_adjustments_created}</p>
                                        </div>
                                        <div className="rounded-lg border p-3">
                                            <p className="text-muted-foreground">Errores</p>
                                            <p className="text-2xl font-semibold">{latestRun.summary.rows_failed}</p>
                                        </div>
                                    </div>
                                )}

                                {latestRun.status === 'ready' && (
                                    <Button onClick={() => handleConfirm(latestRun.id)}>Confirmar y aplicar cambios</Button>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Errores recientes</CardTitle>
                                <CardDescription>Se muestran hasta 10 errores de la ultima carga.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {latestErrors.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No hay errores en la ultima carga.</p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Fila</TableHead>
                                                <TableHead>Campo</TableHead>
                                                <TableHead>Mensaje</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {latestErrors.map((errorItem) => (
                                                <TableRow key={`${errorItem.row}-${errorItem.field}-${errorItem.message}`}>
                                                    <TableCell>{errorItem.row}</TableCell>
                                                    <TableCell>{errorItem.field}</TableCell>
                                                    <TableCell>{errorItem.message}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {latestRun?.preview_rows && latestRun.preview_rows.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Confirmacion previa</CardTitle>
                            <CardDescription>Estos son los cambios detectados para cada producto antes de confirmar.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fila</TableHead>
                                        <TableHead>SKU</TableHead>
                                        <TableHead>Producto</TableHead>
                                        <TableHead>Cambios</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {latestRun.preview_rows.map((row, index) => {
                                        const changes = Array.isArray(row.changes) ? row.changes : [];

                                        return (
                                            <TableRow key={`${row.row ?? index}-${row.sku ?? index}`}>
                                                <TableCell>{row.row ?? '-'}</TableCell>
                                                <TableCell>{row.sku ?? '-'}</TableCell>
                                                <TableCell>{row.product_name ?? '-'}</TableCell>
                                                <TableCell>
                                                    {changes.length === 0 ? (
                                                        <span className="text-sm text-muted-foreground">Sin cambios</span>
                                                    ) : (
                                                        <div className="space-y-1 text-sm">
                                                            {changes.map((change, changeIndex) => (
                                                                <div
                                                                    key={`${row.row ?? index}-${change.field ?? changeIndex}-${change.to ?? changeIndex}`}
                                                                >
                                                                    <span className="font-medium">{change.field}</span>: {change.from ?? '-'} -&gt;{' '}
                                                                    {change.to ?? '-'}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Historial</CardTitle>
                        <CardDescription>Ultimas cargas realizadas en esta herramienta.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {bulkUpdates.data.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Todavia no hay cargas registradas.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Archivo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Procesadas</TableHead>
                                        <TableHead>Actualizadas</TableHead>
                                        <TableHead>Errores</TableHead>
                                        <TableHead>Fecha</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bulkUpdates.data.map((bulkUpdate) => (
                                        <TableRow key={bulkUpdate.id}>
                                            <TableCell>{bulkUpdate.original_filename}</TableCell>
                                            <TableCell>
                                                <Badge variant={statusVariant(bulkUpdate.status)}>{statusLabel(bulkUpdate.status)}</Badge>
                                            </TableCell>
                                            <TableCell>{bulkUpdate.processed_rows}</TableCell>
                                            <TableCell>{bulkUpdate.summary?.products_updated ?? 0}</TableCell>
                                            <TableCell>{bulkUpdate.error_rows}</TableCell>
                                            <TableCell>{new Date(bulkUpdate.created_at).toLocaleString()}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
