import { Link, router } from '@inertiajs/react';
import { Paperclip, Plus, Save, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ServerSearchableSelect, type ServerSearchableSelectOption } from '@/components/ui/server-searchable-select';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/hooks/use-permissions';
import { type Media, type Workspace } from '@/types';
import { useCurrency } from '@/utils/currency';

export interface SupplierSearchResult {
    id: number;
    name: string;
    phone_primary: string | null;
    identification_number: string | null;
    email: string | null;
}

export interface ExpenseFormData {
    workspace_id: number | null;
    contact_id: number | null;
    document_number: string;
    issue_date: string;
    subtotal_amount: string;
    itbis_amount: string;
    isc_amount: string;
    withheld_itbis_amount: string;
    withheld_isr_amount: string;
    is_informal: boolean;
    status: string;
    notes: string;
    attachments: File[];
    remove_attachment_ids: number[];
}

interface ExpenseFormProps {
    mode: 'create' | 'edit';
    expenseId?: number;
    data: ExpenseFormData;
    setData: <K extends keyof ExpenseFormData>(key: K, value: ExpenseFormData[K]) => void;
    errors: Record<string, string>;
    processing: boolean;
    onSubmit: (e: React.FormEvent) => void;
    statuses: Record<string, string>;
    currentWorkspace?: Workspace | null;
    availableWorkspaces: Workspace[];
    supplierSearchResults?: SupplierSearchResult[];
    initialSupplier?: SupplierSearchResult | null;
    existingAttachments?: Media[];
}

const parseAmount = (value: string): number => {
    const normalized = value.trim().replace(',', '.');
    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

export default function ExpenseForm({
    mode,
    expenseId,
    data,
    setData,
    errors,
    processing,
    onSubmit,
    statuses,
    currentWorkspace,
    availableWorkspaces,
    supplierSearchResults = [],
    initialSupplier = null,
    existingAttachments = [],
}: ExpenseFormProps) {
    const { can } = usePermissions();
    const { format: formatCurrency } = useCurrency();
    const [selectedSupplier, setSelectedSupplier] = useState<SupplierSearchResult | null>(initialSupplier);
    const [supplierSearchQuery, setSupplierSearchQuery] = useState('');
    const [isSearchingSuppliers, setIsSearchingSuppliers] = useState(false);
    const supplierSearchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (initialSupplier && data.contact_id === initialSupplier.id && selectedSupplier?.id !== initialSupplier.id) {
            setSelectedSupplier(initialSupplier);
        }
    }, [data.contact_id, initialSupplier, selectedSupplier?.id]);

    useEffect(() => {
        return () => {
            if (supplierSearchTimeoutRef.current) {
                clearTimeout(supplierSearchTimeoutRef.current);
            }
        };
    }, []);

    const existingVisibleAttachments = useMemo(() => {
        return existingAttachments.filter((attachment) => !data.remove_attachment_ids.includes(attachment.id));
    }, [data.remove_attachment_ids, existingAttachments]);

    const supplierOptions: ServerSearchableSelectOption[] = (supplierSearchQuery.length >= 2 ? supplierSearchResults : []).map((supplier) => ({
        value: supplier.id.toString(),
        label: supplier.name,
    }));

    const totalAmount = useMemo(() => {
        return (
            parseAmount(data.subtotal_amount) +
            parseAmount(data.itbis_amount) +
            parseAmount(data.isc_amount) -
            parseAmount(data.withheld_itbis_amount) -
            parseAmount(data.withheld_isr_amount)
        );
    }, [data.isc_amount, data.itbis_amount, data.subtotal_amount, data.withheld_isr_amount, data.withheld_itbis_amount]);

    const attachmentError = errors.attachments ?? Object.entries(errors).find(([key]) => key.startsWith('attachments.'))?.[1];

    const handleSupplierSearch = (query: string) => {
        setSupplierSearchQuery(query);

        if (supplierSearchTimeoutRef.current) {
            clearTimeout(supplierSearchTimeoutRef.current);
        }

        if (query.trim().length < 2) {
            setIsSearchingSuppliers(false);

            return;
        }

        setIsSearchingSuppliers(true);

        supplierSearchTimeoutRef.current = setTimeout(() => {
            router.reload({
                only: ['supplierSearchResults'],
                data: { supplier_search: query },
                onFinish: () => setIsSearchingSuppliers(false),
            });
        }, 300);
    };

    const handleSupplierChange = (value: string) => {
        const selectedId = Number.parseInt(value, 10);
        const supplier = supplierSearchResults.find((option) => option.id === selectedId) ?? selectedSupplier;

        setData('contact_id', selectedId);
        setSelectedSupplier(supplier ?? null);
    };

    const handleWorkspaceChange = (value: string) => {
        setData('workspace_id', Number.parseInt(value, 10));
    };

    const handleAddAttachments = (event: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []);

        if (files.length === 0) {
            return;
        }

        setData('attachments', [...data.attachments, ...files]);
        event.target.value = '';
    };

    const removeNewAttachment = (index: number) => {
        setData(
            'attachments',
            data.attachments.filter((_, currentIndex) => currentIndex !== index),
        );
    };

    const markExistingAttachmentForRemoval = (attachmentId: number) => {
        if (data.remove_attachment_ids.includes(attachmentId)) {
            return;
        }

        setData('remove_attachment_ids', [...data.remove_attachment_ids, attachmentId]);
    };

    const pageTitle = mode === 'create' ? 'Nuevo gasto' : 'Editar gasto';

    return (
        <form onSubmit={onSubmit} className="space-y-8">
            <div className="flex items-center justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{pageTitle}</h1>
                    <p className="text-gray-600 dark:text-gray-400">Registra gastos de suplidores con su comprobante, impuestos y adjuntos.</p>
                </div>

                <div className="flex items-center gap-2">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/expenses">Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Guardando...' : 'Guardar gasto'}
                    </Button>
                </div>
            </div>

            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div className="space-y-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información general</CardTitle>
                            <CardDescription>Define la sucursal, el suplidor y los datos del comprobante.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-6 md:grid-cols-2">
                            {can('view all locations') && availableWorkspaces.length > 0 && (
                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="workspace_id">Sucursal</Label>
                                    <Select value={data.workspace_id?.toString() ?? ''} onValueChange={handleWorkspaceChange}>
                                        <SelectTrigger id="workspace_id" className={errors.workspace_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona una sucursal" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableWorkspaces.map((workspace) => (
                                                <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                    {workspace.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.workspace_id && <p className="text-sm text-red-600">{errors.workspace_id}</p>}
                                </div>
                            )}

                            {!can('view all locations') && currentWorkspace && (
                                <div className="space-y-2 md:col-span-2">
                                    <Label>Sucursal</Label>
                                    <Input value={currentWorkspace.name} readOnly disabled />
                                </div>
                            )}

                            <div className="space-y-2 md:col-span-2">
                                <Label>Suplidor</Label>
                                <ServerSearchableSelect
                                    options={supplierOptions}
                                    value={data.contact_id?.toString()}
                                    selectedLabel={selectedSupplier?.name ?? undefined}
                                    onValueChange={handleSupplierChange}
                                    onSearchChange={handleSupplierSearch}
                                    placeholder="Selecciona un suplidor"
                                    searchPlaceholder="Buscar suplidor..."
                                    emptyText="No se encontraron suplidores"
                                    loadingText="Buscando suplidores..."
                                    isLoading={isSearchingSuppliers}
                                    triggerClassName={errors.contact_id ? 'border-red-500' : ''}
                                />
                                {errors.contact_id && <p className="text-sm text-red-600">{errors.contact_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="document_number">Número de comprobante/factura</Label>
                                <Input
                                    id="document_number"
                                    value={data.document_number}
                                    onChange={(event) => setData('document_number', event.target.value)}
                                    className={errors.document_number ? 'border-red-500' : ''}
                                />
                                {errors.document_number && <p className="text-sm text-red-600">{errors.document_number}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="issue_date">Fecha</Label>
                                <Input
                                    id="issue_date"
                                    type="date"
                                    value={data.issue_date}
                                    onChange={(event) => setData('issue_date', event.target.value)}
                                    className={errors.issue_date ? 'border-red-500' : ''}
                                />
                                {errors.issue_date && <p className="text-sm text-red-600">{errors.issue_date}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Estado</Label>
                                <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                    <SelectTrigger id="status" className={errors.status ? 'border-red-500' : ''}>
                                        <SelectValue placeholder="Selecciona un estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(statuses).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-sm text-red-600">{errors.status}</p>}
                            </div>

                            <div className="flex items-center gap-3 rounded-lg border p-4 md:col-span-2">
                                <Checkbox
                                    id="is_informal"
                                    checked={data.is_informal}
                                    onCheckedChange={(checked) => setData('is_informal', checked === true)}
                                />
                                <div className="space-y-1">
                                    <Label htmlFor="is_informal">Marcar como gasto informal</Label>
                                    <p className="text-sm text-gray-500">Activa esta opción si la factura no tiene valor fiscal.</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Montos e impuestos</CardTitle>
                            <CardDescription>Registra los montos de la factura y las retenciones aplicadas.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="subtotal_amount">Subtotal</Label>
                                <Input
                                    id="subtotal_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.subtotal_amount}
                                    onChange={(event) => setData('subtotal_amount', event.target.value)}
                                    className={errors.subtotal_amount ? 'border-red-500' : ''}
                                />
                                {errors.subtotal_amount && <p className="text-sm text-red-600">{errors.subtotal_amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="itbis_amount">ITBIS</Label>
                                <Input
                                    id="itbis_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.itbis_amount}
                                    onChange={(event) => setData('itbis_amount', event.target.value)}
                                    className={errors.itbis_amount ? 'border-red-500' : ''}
                                />
                                {errors.itbis_amount && <p className="text-sm text-red-600">{errors.itbis_amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="isc_amount">ISC</Label>
                                <Input
                                    id="isc_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.isc_amount}
                                    onChange={(event) => setData('isc_amount', event.target.value)}
                                    className={errors.isc_amount ? 'border-red-500' : ''}
                                />
                                {errors.isc_amount && <p className="text-sm text-red-600">{errors.isc_amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="withheld_itbis_amount">Retención ITBIS</Label>
                                <Input
                                    id="withheld_itbis_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.withheld_itbis_amount}
                                    onChange={(event) => setData('withheld_itbis_amount', event.target.value)}
                                    className={errors.withheld_itbis_amount ? 'border-red-500' : ''}
                                />
                                {errors.withheld_itbis_amount && <p className="text-sm text-red-600">{errors.withheld_itbis_amount}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="withheld_isr_amount">Retención ISR</Label>
                                <Input
                                    id="withheld_isr_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.withheld_isr_amount}
                                    onChange={(event) => setData('withheld_isr_amount', event.target.value)}
                                    className={errors.withheld_isr_amount ? 'border-red-500' : ''}
                                />
                                {errors.withheld_isr_amount && <p className="text-sm text-red-600">{errors.withheld_isr_amount}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="notes">Notas</Label>
                                <Textarea id="notes" value={data.notes} onChange={(event) => setData('notes', event.target.value)} rows={4} />
                                {errors.notes && <p className="text-sm text-red-600">{errors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Adjuntos</CardTitle>
                            <CardDescription>Sube PDF o imágenes de la factura.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center gap-3">
                                <Input
                                    type="file"
                                    multiple
                                    accept="application/pdf,image/jpeg,image/png,image/gif,image/webp"
                                    onChange={handleAddAttachments}
                                />
                            </div>

                            {attachmentError && <p className="text-sm text-red-600">{attachmentError}</p>}

                            {existingVisibleAttachments.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-gray-900">Adjuntos existentes</p>
                                    <div className="space-y-2">
                                        {existingVisibleAttachments.map((attachment) => (
                                            <div key={attachment.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <a
                                                    href={attachment.original_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="flex items-center gap-2 text-sm text-primary hover:underline"
                                                >
                                                    <Paperclip className="h-4 w-4" />
                                                    {attachment.file_name}
                                                </a>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => markExistingAttachmentForRemoval(attachment.id)}
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {data.attachments.length > 0 && (
                                <div className="space-y-2">
                                    <p className="text-sm font-medium text-gray-900">Nuevos adjuntos</p>
                                    <div className="space-y-2">
                                        {data.attachments.map((attachment, index) => (
                                            <div
                                                key={`${attachment.name}-${index}`}
                                                className="flex items-center justify-between rounded-lg border p-3 text-sm"
                                            >
                                                <span className="flex items-center gap-2 text-gray-700">
                                                    <Paperclip className="h-4 w-4" />
                                                    {attachment.name}
                                                </span>
                                                <Button type="button" variant="ghost" size="sm" onClick={() => removeNewAttachment(index)}>
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen</CardTitle>
                            <CardDescription>Total neto calculado automáticamente.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="rounded-lg bg-primary/5 p-4">
                                <p className="text-sm text-gray-500">Total neto</p>
                                <p className="text-2xl font-semibold text-gray-900">{formatCurrency(totalAmount)}</p>
                            </div>

                            <div className="space-y-3 text-sm text-gray-600">
                                <div className="flex items-center justify-between">
                                    <span>Subtotal</span>
                                    <span>{formatCurrency(parseAmount(data.subtotal_amount))}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>ITBIS</span>
                                    <span>{formatCurrency(parseAmount(data.itbis_amount))}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>ISC</span>
                                    <span>{formatCurrency(parseAmount(data.isc_amount))}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Retención ITBIS</span>
                                    <span>-{formatCurrency(parseAmount(data.withheld_itbis_amount))}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span>Retención ISR</span>
                                    <span>-{formatCurrency(parseAmount(data.withheld_isr_amount))}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Suplidor seleccionado</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm text-gray-600">
                            <div>
                                <p className="font-medium text-gray-900">{selectedSupplier?.name ?? 'Sin suplidor seleccionado'}</p>
                            </div>
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">RNC o Cédula</p>
                                <p>{selectedSupplier?.identification_number ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">Teléfono</p>
                                <p>{selectedSupplier?.phone_primary ?? '-'}</p>
                            </div>
                            <div>
                                <p className="text-xs tracking-wide text-gray-500 uppercase">Email</p>
                                <p>{selectedSupplier?.email ?? '-'}</p>
                            </div>
                            <Button type="button" variant="outline" size="sm" asChild>
                                <Link href="/contacts/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear suplidor
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    );
}
