import { ArrowLeft, Check, Download, Eye, Play, X } from 'lucide-react';
import { useState } from 'react';

import { index, process, update } from '@/actions/App/Http/Controllers/ProductImportController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ProductImport } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Importación de Productos',
        href: index().url,
    },
    {
        title: 'Detalles de Importación',
        href: '',
    },
];

interface Props {
    import: ProductImport;
    availableFields: Array<{
        key: string;
        label: string;
        required: boolean;
    }>;
    stockFields: Array<{
        key: string;
        label: string;
        required: boolean;
    }>;
    workspaces: Array<{
        id: number;
        name: string;
    }>;
    previewData: any[];
}

export default function ProductImportsShow({ import: productImport, availableFields, stockFields, workspaces, previewData }: Props) {
    const [processing, setProcessing] = useState(false);
    
    const { data, setData, patch, errors, processing: updating } = useForm({
        column_mapping: productImport.column_mapping || {},
        workspaces: [] as number[],
        stock_mapping: {} as Record<string, Record<string, string>>, // workspace_id -> stock_field -> excel_column
    });

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

    const handleColumnMapping = (field: string, column: string) => {
        const newMapping = { ...(data.column_mapping || {}) };
        
        // Remove any existing mapping to this field
        Object.keys(newMapping).forEach(existingColumn => {
            if (newMapping[existingColumn] === field) {
                delete newMapping[existingColumn];
            }
        });
        
        // Add new mapping if not 'none'
        if (column !== 'none') {
            newMapping[column] = field;
        }
        
        setData('column_mapping', newMapping);
    };

    const handleWorkspaceSelection = (workspaceId: number, selected: boolean) => {
        const newWorkspaces = selected 
            ? [...data.workspaces, workspaceId]
            : data.workspaces.filter(id => id !== workspaceId);
        setData('workspaces', newWorkspaces);
    };

    const handleStockMapping = (workspaceId: number, stockField: string, column: string) => {
        const newStockMapping = { ...data.stock_mapping };
        if (!newStockMapping[workspaceId]) {
            newStockMapping[workspaceId] = {};
        }
        if (column === 'none') {
            delete newStockMapping[workspaceId][stockField];
        } else {
            newStockMapping[workspaceId][stockField] = column;
        }
        setData('stock_mapping', newStockMapping);
    };

    const saveMapping = () => {
        patch(update(productImport.id).url);
    };

    const startProcessing = () => {
        if (!isReadyToProcess()) return;
        
        setProcessing(true);
        router.post(process(productImport.id).url, {
            workspaces: data.workspaces,
            stock_mapping: data.stock_mapping,
        }, {
            onFinish: () => setProcessing(false),
        });
    };

    const isReadyToProcess = () => {
        if (!availableFields || !Array.isArray(availableFields)) return false;
        const requiredFields = availableFields.filter(field => field.required).map(field => field.key);
        const mappedFields = Object.values(data.column_mapping || {});
        const hasRequiredFields = requiredFields.every(field => mappedFields.includes(field));
        const hasSelectedWorkspaces = data.workspaces.length > 0;
        return hasRequiredFields && hasSelectedWorkspaces;
    };

    const getColumnForField = (field: string) => {
        // Find which column is mapped to this field
        for (const [column, mappedField] of Object.entries(data.column_mapping || {})) {
            if (mappedField === field) {
                return column;
            }
        }
        return 'none';
    };

    const getFieldForColumn = (column: string) => {
        return data.column_mapping?.[column] || null;
    };

    const isColumnMapped = (column: string) => {
        return Object.keys(data.column_mapping || {}).includes(column);
    };

    const getMappingErrors = () => {
        if (!availableFields || !Array.isArray(availableFields)) return [];
        const requiredFields = availableFields.filter(field => field.required);
        const mappedFields = Object.values(data.column_mapping || {});
        return requiredFields.filter(field => !mappedFields.includes(field.key));
    };

    const mappingErrors = getMappingErrors();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Importación: ${productImport.original_filename}`} />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{productImport.original_filename}</h1>
                            <div className="flex items-center gap-2 mt-1">
                                <Badge variant={getStatusBadgeVariant(productImport.status)}>
                                    {getStatusText(productImport.status)}
                                </Badge>
                                <span className="text-sm text-muted-foreground">
                                    {productImport.total_rows} filas
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Column Mapping Section */}
                {productImport.status === 'mapping' && (
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Mapear Columnas</CardTitle>
                                <CardDescription>
                                    Para cada campo del sistema, seleccione la columna de Excel que contiene esa información
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {availableFields && Array.isArray(availableFields) && availableFields.map((field) => (
                                        <div key={field.key} className="space-y-2">
                                            <Label>
                                                {field.label} {field.required && <span className="text-red-500">*</span>}
                                            </Label>
                                            <Select
                                                value={getColumnForField(field.key)}
                                                onValueChange={(value) => handleColumnMapping(field.key, value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccione una columna" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">No mapear</SelectItem>
                                                    {productImport.headers?.map((header: string) => (
                                                        <SelectItem
                                                            key={header}
                                                            value={header}
                                                            disabled={isColumnMapped(header) && getColumnForField(field.key) !== header}
                                                        >
                                                            {header}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    ))}
                                </div>

                                {mappingErrors.length > 0 && (
                                    <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <h4 className="font-medium text-red-900 mb-2">Campos requeridos sin mapear:</h4>
                                        <ul className="list-disc list-inside text-sm text-red-700 space-y-1">
                                            {mappingErrors.map((field) => (
                                                <li key={field.key}>{field.label}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {errors.workspaces && (
                                    <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <p className="text-sm text-red-700">{errors.workspaces}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Workspace Selection and Stock Mapping */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Seleccionar Sucursales e Inventario</CardTitle>
                                <CardDescription>
                                    Seleccione las sucursales donde se importarán los productos y configure el mapeo de inventario
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Workspace Selection */}
                                <div>
                                    <Label className="text-base font-medium">Sucursales disponibles</Label>
                                    <div className="mt-3 space-y-3">
                                        {workspaces?.map((workspace) => (
                                            <div key={workspace.id} className="flex items-start space-x-3">
                                                <Checkbox
                                                    id={`workspace-${workspace.id}`}
                                                    checked={data.workspaces.includes(workspace.id)}
                                                    onCheckedChange={(checked) => handleWorkspaceSelection(workspace.id, !!checked)}
                                                />
                                                <div className="flex-1">
                                                    <Label htmlFor={`workspace-${workspace.id}`} className="font-medium">
                                                        {workspace.name}
                                                    </Label>
                                                    
                                                    {/* Stock mapping for this workspace */}
                                                    {data.workspaces.includes(workspace.id) && (
                                                        <div className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                                                            <div className="md:col-span-3">
                                                                <Label className="text-sm font-medium text-gray-700">
                                                                    Mapeo de inventario para {workspace.name}
                                                                </Label>
                                                            </div>
                                                            {stockFields?.map((stockField) => (
                                                                <div key={stockField.key} className="space-y-2">
                                                                    <Label className="text-sm">{stockField.label}</Label>
                                                                    <Select
                                                                        value={data.stock_mapping[workspace.id]?.[stockField.key] || 'none'}
                                                                        onValueChange={(value) => handleStockMapping(workspace.id, stockField.key, value)}
                                                                    >
                                                                        <SelectTrigger className="h-8">
                                                                            <SelectValue placeholder="Seleccionar columna" />
                                                                        </SelectTrigger>
                                                                        <SelectContent>
                                                                            <SelectItem value="none">No mapear</SelectItem>
                                                                            {productImport.headers?.map((header: string) => (
                                                                                <SelectItem key={header} value={header}>
                                                                                    {header}
                                                                                </SelectItem>
                                                                            ))}
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex gap-3">
                                    <Button
                                        onClick={saveMapping}
                                        disabled={updating}
                                        variant="outline"
                                    >
                                        Guardar Mapeo
                                    </Button>
                                    <Button
                                        onClick={startProcessing}
                                        disabled={!isReadyToProcess() || processing}
                                    >
                                        {processing ? (
                                            <>
                                                <Play className="mr-2 h-4 w-4 animate-spin" />
                                                Procesando...
                                            </>
                                        ) : (
                                            <>
                                                <Play className="mr-2 h-4 w-4" />
                                                Procesar Importación
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Preview Data */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Vista Previa de Datos</CardTitle>
                                <CardDescription>
                                    Primeras 5 filas del archivo para verificar el mapeo
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            {productImport.headers?.map((header: string) => (
                                                <TableHead key={header}>
                                                    {header}
                                                    <div className="text-xs text-muted-foreground mt-1">
                                                        {getFieldForColumn(header)
                                                            ? availableFields && Array.isArray(availableFields) 
                                                              ? availableFields.find((f) => f.key === getFieldForColumn(header))?.label
                                                              : 'Campo desconocido'
                                                            : 'Sin mapear'
                                                        }
                                                    </div>
                                                </TableHead>
                                            ))}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {previewData.map((row: any, index: number) => (
                                            <TableRow key={index}>
                                                {productImport.headers?.map((header: string) => (
                                                    <TableCell key={`${index}-${header}`}>
                                                        {row[header] || '-'}
                                                    </TableCell>
                                                ))}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Import Results */}
                {['completed', 'failed'].includes(productImport.status) && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Productos Creados
                                </CardTitle>
                                <Check className="h-4 w-4 text-green-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {productImport.import_summary?.products_created || 0}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    nuevos productos añadidos
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Productos Actualizados
                                </CardTitle>
                                <Eye className="h-4 w-4 text-blue-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {productImport.import_summary?.products_updated || 0}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    productos modificados
                                </p>
                            </CardContent>
                        </Card>
                        
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Errores
                                </CardTitle>
                                <X className="h-4 w-4 text-red-600" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    {productImport.error_count}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    filas con problemas
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Validation Errors */}
                {productImport.validation_errors && productImport.validation_errors.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Errores de Validación</CardTitle>
                            <CardDescription>
                                Errores encontrados durante la importación
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Fila</TableHead>
                                        <TableHead>Campo</TableHead>
                                        <TableHead>Error</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {productImport.validation_errors.slice(0, 50).map((error: any, index: number) => (
                                        <TableRow key={index}>
                                            <TableCell>{error.row}</TableCell>
                                            <TableCell>{error.field}</TableCell>
                                            <TableCell className="text-red-600">{error.message}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {productImport.validation_errors.length > 50 && (
                                <p className="text-sm text-muted-foreground mt-4">
                                    Mostrando los primeros 50 errores de {productImport.validation_errors.length} total
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}