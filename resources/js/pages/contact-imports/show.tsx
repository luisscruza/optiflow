import { ArrowLeft, Check, Play, X } from 'lucide-react';
import { useState } from 'react';

import { index as contactsIndex } from '@/actions/App/Http/Controllers/ContactController';
import { update } from '@/actions/App/Http/Controllers/ContactImportController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { process } from '@/routes/contact-imports';
import { type BreadcrumbItem, type ContactImport } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';

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

interface Props {
    import: ContactImport;
    availableFields: Array<{
        key: string;
        label: string;
        required: boolean;
    }>;
    previewData: any[];
}

export default function ContactImportsShow({ import: contactImport, availableFields, previewData }: Props) {
    const [processing, setProcessing] = useState(false);

    const {
        data,
        setData,
        patch,
        errors,
        processing: updating,
    } = useForm<{
        column_mapping: Record<string, string>;
    }>({
        column_mapping: contactImport.column_mapping || {},
    });

    const getStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'completed':
                return 'default';
            case 'failed':
                return 'destructive';
            case 'processing':
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

        Object.keys(newMapping).forEach((existingColumn) => {
            if (newMapping[existingColumn] === field) {
                delete newMapping[existingColumn];
            }
        });

        if (column !== 'none') {
            newMapping[column] = field;
        }

        setData('column_mapping', newMapping);
    };

    const saveMapping = () => {
        patch(update(contactImport.id).url);
    };

    const startProcessing = () => {
        if (!isReadyToProcess()) return;

        setProcessing(true);
        router.post(
            process(contactImport.id).url,
            {},
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    const isReadyToProcess = () => {
        if (!availableFields || !Array.isArray(availableFields)) return false;
        const requiredFields = availableFields.filter((field) => field.required).map((field) => field.key);
        const mappedFields = Object.values(data.column_mapping || {});
        return requiredFields.every((field) => mappedFields.includes(field));
    };

    const getColumnForField = (field: string) => {
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

    const mappingErrors = availableFields
        .filter((field) => field.required)
        .filter((field) => !Object.values(data.column_mapping || {}).includes(field.key));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Importación: ${contactImport.original_filename}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Button variant="outline" size="sm" asChild>
                                <Link href={contactsIndex().url}>
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                    Volver
                                </Link>
                            </Button>

                            <div>
                                <h1 className="text-3xl font-bold tracking-tight">{contactImport.original_filename}</h1>
                                <div className="mt-1 flex items-center gap-2">
                                    <Badge variant={getStatusBadgeVariant(contactImport.status)}>{getStatusText(contactImport.status)}</Badge>
                                    <span className="text-sm text-muted-foreground">{contactImport.total_rows} filas</span>
                                    {contactImport.source_files && contactImport.source_files.length > 1 && (
                                        <span className="text-sm text-muted-foreground">{contactImport.source_files.length} archivos</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {contactImport.status === 'mapping' && (
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Mapear Columnas</CardTitle>
                                    <CardDescription>Seleccione la columna del CSV que corresponde a cada campo</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        {availableFields.map((field) => (
                                            <div key={field.key} className="space-y-2">
                                                <Label>
                                                    {field.label} {field.required && <span className="text-red-500">*</span>}
                                                </Label>
                                                <SearchableSelect
                                                    value={getColumnForField(field.key)}
                                                    onValueChange={(value) => handleColumnMapping(field.key, value)}
                                                    placeholder="Seleccione una columna"
                                                    searchPlaceholder="Buscar columna"
                                                    emptyText="No se encontraron columnas"
                                                    options={[
                                                        { value: 'none', label: 'No mapear' },
                                                        ...(contactImport.headers || []).map(
                                                            (header: string): SearchableSelectOption => ({
                                                                value: header,
                                                                label: header,
                                                                disabled: isColumnMapped(header) && getColumnForField(field.key) !== header,
                                                            }),
                                                        ),
                                                    ]}
                                                />
                                            </div>
                                        ))}
                                    </div>

                                    {mappingErrors.length > 0 && (
                                        <div className="rounded-lg border border-red-200 bg-red-50 p-4">
                                            <h4 className="mb-2 font-medium text-red-900">Campos requeridos sin mapear:</h4>
                                            <ul className="list-inside list-disc space-y-1 text-sm text-red-700">
                                                {mappingErrors.map((field) => (
                                                    <li key={field.key}>{field.label}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}

                                    {errors.column_mapping && (
                                        <div className="rounded-lg border border-red-200 bg-red-50 p-4">
                                            <p className="text-sm text-red-700">{errors.column_mapping}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <div className="flex gap-3">
                                <Button onClick={saveMapping} disabled={updating} variant="outline">
                                    Guardar mapeo
                                </Button>
                                <Button onClick={startProcessing} disabled={!isReadyToProcess() || processing}>
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

                            <Card>
                                <CardHeader>
                                    <CardTitle>Vista Previa de Datos</CardTitle>
                                    <CardDescription>Primeras 5 filas del archivo para verificar el mapeo</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                {contactImport.headers?.map((header: string) => (
                                                    <TableHead key={header}>
                                                        {header}
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            {getFieldForColumn(header)
                                                                ? availableFields.find((field) => field.key === getFieldForColumn(header))?.label
                                                                : 'Sin mapear'}
                                                        </div>
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {previewData.map((row: any, index: number) => (
                                                <TableRow key={index}>
                                                    {contactImport.headers?.map((header: string) => (
                                                        <TableCell key={`${index}-${header}`}>{row[header] || '-'}</TableCell>
                                                    ))}
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {['completed', 'failed'].includes(contactImport.status) && (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">Contactos Importados</CardTitle>
                                    <Check className="h-4 w-4 text-green-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{contactImport.import_summary?.imported || 0}</div>
                                    <p className="text-xs text-muted-foreground">contactos creados</p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">Errores</CardTitle>
                                    <X className="h-4 w-4 text-red-600" />
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">{contactImport.error_rows || 0}</div>
                                    <p className="text-xs text-muted-foreground">filas con problemas</p>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {contactImport.validation_errors && contactImport.validation_errors.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Errores de Validación</CardTitle>
                                <CardDescription>Errores encontrados durante la importación</CardDescription>
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
                                        {contactImport.validation_errors.slice(0, 50).map((error: any, index: number) => (
                                            <TableRow key={index}>
                                                <TableCell>{error.row}</TableCell>
                                                <TableCell>{error.field}</TableCell>
                                                <TableCell className="text-red-600">{error.message}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                                {contactImport.validation_errors.length > 50 && (
                                    <p className="mt-4 text-sm text-muted-foreground">
                                        Mostrando los primeros 50 errores de {contactImport.validation_errors.length} total
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
