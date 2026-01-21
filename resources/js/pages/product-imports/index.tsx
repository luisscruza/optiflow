import { Download, Eye, MoreHorizontal, Plus, Upload } from 'lucide-react';
import { useState } from 'react';

import { create, destroy, index, show } from '@/actions/App/Http/Controllers/ProductImportController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { template } from '@/routes/product-imports';
import { type BreadcrumbItem, type PaginatedData, type ProductImport } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Importación de Productos',
        href: index().url,
    },
];

interface Props {
    imports: PaginatedData<ProductImport>;
}

export default function ProductImportsIndex({ imports }: Props) {
    const [deletingImport, setDeletingImport] = useState<number | null>(null);

    const handleDeleteImport = (importId: number) => {
        if (confirm('¿Está seguro de que desea eliminar esta importación?')) {
            setDeletingImport(importId);
            router.delete(destroy(importId).url, {
                onFinish: () => setDeletingImport(null),
            });
        }
    };

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'completed':
                return 'default';
            case 'failed':
                return 'destructive';
            case 'processing':
                return 'secondary';
            case 'mapping':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case 'pending':
                return 'Pendiente';
            case 'mapping':
                return 'Mapeando';
            case 'processing':
                return 'Procesando';
            case 'completed':
                return 'Completado';
            case 'failed':
                return 'Fallido';
            default:
                return status;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Importación de Productos" />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Importación de Productos</h1>
                        <p className="text-muted-foreground">
                            Importe productos desde archivos Excel de manera masiva
                        </p>
                    </div>
                    
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={template().url}>
                                <Download className="mr-2 h-4 w-4" />
                                Descargar Plantilla
                            </Link>
                        </Button>
                        
                        <Button asChild>
                            <Link href={create().url}>
                                <Plus className="mr-2 h-4 w-4" />
                                Nueva Importación
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Historial de Importaciones</CardTitle>
                        <CardDescription>
                            Revise todas las importaciones de productos realizadas
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {imports.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12">
                                <Upload className="h-12 w-12 text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold">No hay importaciones</h3>
                                <p className="text-sm text-muted-foreground mb-6">
                                    Comience cargando su primer archivo de productos
                                </p>
                                <Button asChild>
                                    <Link href={create().url}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Nueva Importación
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Archivo</TableHead>
                                        <TableHead>Estado</TableHead>
                                        <TableHead>Filas Procesadas</TableHead>
                                        <TableHead>Productos Creados</TableHead>
                                        <TableHead>Productos Actualizados</TableHead>
                                        <TableHead>Errores</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead className="w-[50px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {imports.data.map((importRecord: ProductImport) => (
                                        <TableRow key={importRecord.id}>
                                            <TableCell>
                                                <div className="font-medium">
                                                    {importRecord.original_filename}
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {importRecord.total_rows ? `${importRecord.total_rows} filas` : ''}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getStatusBadgeVariant(importRecord.status)}>
                                                    {getStatusText(importRecord.status)}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {importRecord.processed_rows || 0}
                                            </TableCell>
                                            <TableCell>
                                                {importRecord.import_summary?.products_created || 0}
                                            </TableCell>
                                            <TableCell>
                                                {importRecord.import_summary?.products_updated || 0}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1">
                                                    {importRecord.error_count > 0 && (
                                                        <Badge variant="destructive" className="text-xs">
                                                            {importRecord.error_count}
                                                        </Badge>
                                                    )}
                                                    {importRecord.error_count === 0 && importRecord.status === 'completed' && (
                                                        <span className="text-sm text-muted-foreground">Sin errores</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    {new Date(importRecord.created_at).toLocaleDateString()}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {new Date(importRecord.created_at).toLocaleTimeString()}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem asChild>
                                                            <Link href={show(importRecord.id).url}>
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                Ver Detalles
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            className="text-red-600"
                                                            onClick={() => handleDeleteImport(importRecord.id)}
                                                            disabled={deletingImport === importRecord.id}
                                                        >
                                                            <span className="flex items-center">
                                                                Eliminar
                                                                {deletingImport === importRecord.id && (
                                                                    <span className="ml-2 text-xs">Eliminando...</span>
                                                                )}
                                                            </span>
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
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
