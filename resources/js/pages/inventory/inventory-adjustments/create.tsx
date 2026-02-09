import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Sparkles, X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Ajustes de inventario',
        href: '/inventory-adjustments',
    },
    {
        title: 'Nuevo ajuste',
        href: '/inventory-adjustments/create',
    },
];

interface WorkspaceOption {
    id: number;
    name: string;
}

interface ProductOption {
    id: number;
    name: string;
    sku: string;
    cost: number;
    stocks_by_workspace: Record<string, number>;
}

interface AdjustmentItem {
    product_id: string;
    adjustment_type: 'increment' | 'decrement';
    quantity: string;
}

interface FormData {
    workspace_id: string;
    adjustment_date: string;
    numbering: string;
    notes: string;
    items: AdjustmentItem[];
}

interface Props {
    workspaces: WorkspaceOption[];
    products: ProductOption[];
    current_workspace_id: number | null;
    today: string;
    next_adjustment_number: number;
    initial_product_id?: number | null;
}

const createEmptyItem = (productId?: string): AdjustmentItem => ({
    product_id: productId ?? '',
    adjustment_type: 'increment',
    quantity: '0',
});

export default function InventoryAdjustmentsCreate({
    workspaces,
    products,
    current_workspace_id,
    today,
    next_adjustment_number,
    initial_product_id,
}: Props) {
    const { format: formatCurrency } = useCurrency();

    const { data, setData, post, processing, errors } = useForm<FormData>({
        workspace_id: current_workspace_id ? current_workspace_id.toString() : workspaces[0]?.id?.toString() || '',
        adjustment_date: today,
        numbering: 'principal',
        notes: '',
        items: [createEmptyItem(initial_product_id ? initial_product_id.toString() : undefined)],
    });

    const productOptions: SearchableSelectOption[] = products.map((product) => ({
        value: product.id.toString(),
        label: `${product.name} (${product.sku})`,
    }));

    const getProduct = (productId: string): ProductOption | undefined => {
        return products.find((product) => product.id.toString() === productId);
    };

    const getCurrentQuantity = (item: AdjustmentItem): number => {
        const selectedProduct = getProduct(item.product_id);
        if (!selectedProduct || !data.workspace_id) {
            return 0;
        }

        return Number(selectedProduct.stocks_by_workspace[data.workspace_id] ?? 0);
    };

    const getAverageCost = (item: AdjustmentItem): number => {
        return Number(getProduct(item.product_id)?.cost ?? 0);
    };

    const getQuantity = (item: AdjustmentItem): number => {
        const value = Number.parseFloat(item.quantity || '0');

        return Number.isFinite(value) ? value : 0;
    };

    const getFinalQuantity = (item: AdjustmentItem): number => {
        const currentQuantity = getCurrentQuantity(item);
        const quantity = getQuantity(item);

        return item.adjustment_type === 'increment' ? currentQuantity + quantity : currentQuantity - quantity;
    };

    const getAdjustedTotal = (item: AdjustmentItem): number => {
        const total = getQuantity(item) * getAverageCost(item);

        return item.adjustment_type === 'increment' ? total : -total;
    };

    const updateItem = <K extends keyof AdjustmentItem>(index: number, key: K, value: AdjustmentItem[K]): void => {
        setData(
            'items',
            data.items.map((item, itemIndex) => {
                if (itemIndex !== index) {
                    return item;
                }

                return {
                    ...item,
                    [key]: value,
                };
            }),
        );
    };

    const addItem = (): void => {
        setData('items', [...data.items, createEmptyItem()]);
    };

    const removeItem = (index: number): void => {
        if (data.items.length === 1) {
            setData('items', [createEmptyItem()]);

            return;
        }

        setData(
            'items',
            data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const getItemError = (index: number, field: keyof AdjustmentItem): string | undefined => {
        return errors[`items.${index}.${field}` as keyof typeof errors] as string | undefined;
    };

    const totalAdjusted = data.items.reduce((carry, item) => {
        if (!item.product_id) {
            return carry;
        }

        return carry + getAdjustedTotal(item);
    }, 0);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        post('/inventory-adjustments');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajustes de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-semibold tracking-tight text-foreground">Ajustes de inventario</h1>
                        <p className="text-muted-foreground">
                            Ajusta las cantidades de tus items inventariables registrando incrementos o disminuciones.
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <CardTitle>Detalle del ajuste</CardTitle>
                                    <CardDescription>Indica los detalles generales asociados a este ajuste.</CardDescription>
                                </div>
                                <p className="text-3xl font-semibold tracking-tight text-foreground">No. {next_adjustment_number}</p>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="workspace_id">Sucursal *</Label>
                                    <Select value={data.workspace_id} onValueChange={(value) => setData('workspace_id', value)}>
                                        <SelectTrigger id="workspace_id">
                                            <SelectValue placeholder="Seleccionar sucursal" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {workspaces.map((workspace) => (
                                                <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                    {workspace.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.workspace_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.workspace_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="adjustment_date">Fecha *</Label>
                                    <Input
                                        id="adjustment_date"
                                        type="date"
                                        value={data.adjustment_date}
                                        onChange={(event) => setData('adjustment_date', event.target.value)}
                                        required
                                    />
                                    {errors.adjustment_date && <p className="text-sm text-red-600 dark:text-red-400">{errors.adjustment_date}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="numbering">Numeracion *</Label>
                                    <Select value={data.numbering} onValueChange={(value) => setData('numbering', value)}>
                                        <SelectTrigger id="numbering">
                                            <SelectValue placeholder="Seleccionar numeracion" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="principal">Principal</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes">Observaciones</Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(event) => setData('notes', event.target.value)}
                                    rows={4}
                                    placeholder="Opcional: describe el motivo del ajuste"
                                />
                                {errors.notes && <p className="text-sm text-red-600 dark:text-red-400">{errors.notes}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <CardTitle>Productos ({data.items.length})</CardTitle>
                                    <CardDescription>Selecciona los productos que vas a ajustar.</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="overflow-x-auto rounded-md border">
                                <div className="min-w-[1200px] border-b bg-muted/20 px-3 py-3">
                                    <div className="grid grid-cols-[2.2fr_1.3fr_1.4fr_1.2fr_1.3fr_1.2fr_1.4fr_1.2fr_40px] gap-3 text-sm font-medium text-foreground">
                                        <span>Producto</span>
                                        <span>Cantidad actual</span>
                                        <span>Tipo de ajuste</span>
                                        <span>Cantidad</span>
                                        <span>Costo promedio</span>
                                        <span>Cantidad final</span>
                                        <span>Cantidad final hoy</span>
                                        <span>Total ajustado</span>
                                        <span></span>
                                    </div>
                                </div>

                                <div className="space-y-2 p-3">
                                    {data.items.map((item, index) => {
                                        const currentQuantity = getCurrentQuantity(item);
                                        const averageCost = getAverageCost(item);
                                        const finalQuantity = getFinalQuantity(item);
                                        const adjustedTotal = getAdjustedTotal(item);
                                        const isNegativeLine = adjustedTotal < 0;

                                        return (
                                            <div
                                                key={`adjustment-item-${index}`}
                                                className="grid grid-cols-[2.2fr_1.3fr_1.4fr_1.2fr_1.3fr_1.2fr_1.4fr_1.2fr_40px] gap-3"
                                            >
                                                <div className="space-y-1">
                                                    <SearchableSelect
                                                        options={productOptions}
                                                        value={item.product_id}
                                                        onValueChange={(value) => updateItem(index, 'product_id', value)}
                                                        placeholder="Buscar item inventariable..."
                                                        searchPlaceholder="Escribe para buscar producto..."
                                                        emptyText="No se encontro ningun producto."
                                                        className="max-w-48"
                                                        triggerClassName="h-10 max-w-48 overflow-hidden"
                                                        labelClassName="block max-w-48 overflow-hidden text-left text-ellipsis whitespace-nowrap"
                                                    />
                                                    {getItemError(index, 'product_id') && (
                                                        <p className="text-xs text-red-600 dark:text-red-400">{getItemError(index, 'product_id')}</p>
                                                    )}
                                                </div>

                                                <Input value={currentQuantity.toString()} readOnly className="bg-muted/40" />

                                                <div className="space-y-1">
                                                    <Select
                                                        value={item.adjustment_type}
                                                        onValueChange={(value: 'increment' | 'decrement') =>
                                                            updateItem(index, 'adjustment_type', value)
                                                        }
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="increment">Incremento</SelectItem>
                                                            <SelectItem value="decrement">Disminucion</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                    {getItemError(index, 'adjustment_type') && (
                                                        <p className="text-xs text-red-600 dark:text-red-400">
                                                            {getItemError(index, 'adjustment_type')}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="space-y-1">
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={item.quantity}
                                                        onChange={(event) => updateItem(index, 'quantity', event.target.value)}
                                                    />
                                                    {getItemError(index, 'quantity') && (
                                                        <p className="text-xs text-red-600 dark:text-red-400">{getItemError(index, 'quantity')}</p>
                                                    )}
                                                </div>

                                                <Input value={formatCurrency(averageCost)} readOnly className="bg-muted/40" />
                                                <Input value={finalQuantity.toString()} readOnly className="bg-muted/40" />
                                                <Input value={finalQuantity.toString()} readOnly className="bg-muted/40" />
                                                <p
                                                    className={`self-center text-right text-lg font-semibold ${isNegativeLine ? 'text-red-600' : 'text-emerald-600'}`}
                                                >
                                                    {formatCurrency(adjustedTotal)}
                                                </p>

                                                <Button type="button" variant="ghost" size="icon" onClick={() => removeItem(index)}>
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex items-center justify-between gap-4">
                                <Button type="button" variant="outline" onClick={addItem}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar producto
                                </Button>

                                <p className="text-xl font-semibold tracking-tight text-foreground">Total {formatCurrency(totalAdjusted)}</p>
                            </div>

                            {errors.items && <p className="text-sm text-red-600 dark:text-red-400">{errors.items}</p>}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">Los campos marcados con * son obligatorios.</p>
                        <div className="flex items-center gap-2">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/inventory-adjustments">Cancelar</Link>
                            </Button>
                            <Button type="submit" className="bg-yellow-600 text-white hover:bg-yellow-700" disabled={processing}>
                                {processing ? 'Guardando...' : 'Guardar'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
