import { Head, Link, useForm } from '@inertiajs/react';
import { Check, ChevronDown, ImagePlus, MoreVertical, Save, Tag } from 'lucide-react';
import { useState } from 'react';

import { create, index, store } from '@/actions/App/Http/Controllers/ProductController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Tax } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Productos',
        href: index().url,
    },
    {
        title: 'Crear producto',
        href: create().url,
    },
];

interface Props {
    taxes: Tax[];
    workspace_stocks: Array<{
        workspace_id: number;
        workspace_name: string;
        current_quantity: number;
    }>;
}

function SwitchControl({ checked, onChange, disabled }: { checked: boolean; onChange: (checked: boolean) => void; disabled?: boolean }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={cn(
                'relative inline-flex h-7 w-12 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200',
                'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
                checked ? 'bg-yellow-500' : 'bg-muted',
            )}
        >
            <span
                className={cn(
                    'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-background shadow transition duration-200',
                    checked ? 'translate-x-5' : 'translate-x-0',
                )}
            />
        </button>
    );
}

export default function ProductsCreate({ taxes, workspace_stocks }: Props) {
    const { format: formatCurrency } = useCurrency();
    const defaultTax = taxes.find((tax) => tax.is_default);
    const [showAdvanced, setShowAdvanced] = useState(true);

    const roundCurrency = (value: number): number => {
        return Math.round(value * 100) / 100;
    };

    const normalizeAmount = (value: string): string => {
        return value.replace(/,/g, '');
    };

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        sku: '',
        description: '',
        product_type: 'product' as 'product' | 'service',
        price: '',
        cost: '',
        track_stock: true,
        allow_negative_stock: false,
        default_tax_id: defaultTax ? defaultTax.id.toString() : '',
        minimum_quantity: '',
        unit_cost: '',
        workspace_initial_quantities: workspace_stocks.reduce<Record<string, string>>((accumulator, workspace) => {
            accumulator[workspace.workspace_id.toString()] = '';

            return accumulator;
        }, {}),
    });

    const handleProductTypeChange = (productType: 'product' | 'service') => {
        if (productType === 'service') {
            setData((previous) => ({
                ...previous,
                product_type: 'service',
                track_stock: false,
                allow_negative_stock: false,
            }));

            return;
        }

        setData((previous) => ({
            ...previous,
            product_type: 'product',
            track_stock: true,
        }));
    };

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        post(store().url, {
            onSuccess: () => {
                reset();
            },
        });
    };

    const handleBasePriceChange = (value: string) => {
        setData('price', normalizeAmount(value));
    };

    const handleTotalPriceChange = (value: string) => {
        const normalizedTotal = normalizeAmount(value);

        if (normalizedTotal === '') {
            setData('price', '');

            return;
        }

        const totalInput = Number(normalizedTotal);
        if (!Number.isFinite(totalInput)) {
            return;
        }

        const nextBasePrice = taxRate > 0 ? totalInput / (1 + taxRate / 100) : totalInput;
        setData('price', roundCurrency(nextBasePrice).toString());
    };

    const handleWorkspaceInitialQuantityChange = (workspaceId: number, value: string) => {
        setData('workspace_initial_quantities', {
            ...data.workspace_initial_quantities,
            [workspaceId.toString()]: value,
        });
    };

    const selectedTax = taxes.find((tax) => tax.id.toString() === data.default_tax_id);
    const taxRate = Number(selectedTax?.rate ?? 0);
    const basePrice = Number(data.price || 0);
    const taxAmount = roundCurrency(basePrice * (taxRate / 100));
    const totalPrice = roundCurrency(basePrice + taxAmount);
    const totalPriceDisplay = data.price === '' ? '' : totalPrice.toString();
    const inventoryEnabled = data.product_type === 'product' && data.track_stock;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear producto" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-semibold tracking-tight text-foreground">Crear producto</h1>
                    <p className="mt-2 text-muted-foreground">Completa la ficha de producto con una experiencia mas clara y rapida.</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <div className="space-y-6">
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle>Informacion general</CardTitle>
                                    <CardDescription>Define tipo de producto, referencia y precios base.</CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-6">
                                    <div className="space-y-3">
                                        <Label>Tipo de producto *</Label>
                                        <div className="grid grid-cols-2 gap-3">
                                            <button
                                                type="button"
                                                onClick={() => handleProductTypeChange('product')}
                                                className={cn(
                                                    'flex h-12 items-center justify-between rounded-lg border px-4 text-sm font-medium transition',
                                                    data.product_type === 'product'
                                                        ? 'border-yellow-500 bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300'
                                                        : 'border-border bg-background text-muted-foreground hover:text-foreground',
                                                )}
                                            >
                                                <span>Producto</span>
                                                {data.product_type === 'product' && <Check className="h-4 w-4" />}
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleProductTypeChange('service')}
                                                className={cn(
                                                    'flex h-12 items-center justify-between rounded-lg border px-4 text-sm font-medium transition',
                                                    data.product_type === 'service'
                                                        ? 'border-yellow-500 bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300'
                                                        : 'border-border bg-background text-muted-foreground hover:text-foreground',
                                                )}
                                            >
                                                <span>Servicio</span>
                                                {data.product_type === 'service' && <Check className="h-4 w-4" />}
                                            </button>
                                        </div>
                                        {errors.product_type && <p className="text-sm text-red-600 dark:text-red-400">{errors.product_type}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre *</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(event) => setData('name', event.target.value)}
                                            placeholder="Ej. Acuvue 1 Day Moist (45 pares)"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="sku">Referencia (SKU) *</Label>
                                            <Input
                                                id="sku"
                                                value={data.sku}
                                                onChange={(event) => setData('sku', event.target.value)}
                                                placeholder="Ej. LEN-9034"
                                                required
                                            />
                                            {errors.sku && <p className="text-sm text-red-600 dark:text-red-400">{errors.sku}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="cost">Costo por unidad</Label>
                                            <Input
                                                id="cost"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={data.cost}
                                                onChange={(event) => {
                                                    const newCost = event.target.value;

                                                    if (inventoryEnabled && (!data.unit_cost || data.unit_cost === data.cost)) {
                                                        setData((previous) => ({
                                                            ...previous,
                                                            cost: newCost,
                                                            unit_cost: newCost,
                                                        }));

                                                        return;
                                                    }

                                                    setData('cost', newCost);
                                                }}
                                                placeholder="0.00"
                                            />
                                            {errors.cost && <p className="text-sm text-red-600 dark:text-red-400">{errors.cost}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-3 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)_auto_minmax(0,1fr)]">
                                        <div className="space-y-2">
                                            <Label htmlFor="price">Precio base *</Label>
                                            <Input
                                                id="price"
                                                type="text"
                                                inputMode="decimal"
                                                value={data.price}
                                                onChange={(event) => handleBasePriceChange(event.target.value)}
                                                placeholder="0.00"
                                                required
                                            />
                                            {errors.price && <p className="text-sm text-red-600 dark:text-red-400">{errors.price}</p>}
                                        </div>

                                        <div className="hidden items-end justify-center pb-2 text-2xl font-semibold text-yellow-600 md:flex">+</div>

                                        <div className="space-y-2">
                                            <Label htmlFor="default_tax_id">Impuesto</Label>
                                            <Select
                                                value={data.default_tax_id || 'none'}
                                                onValueChange={(value) => setData('default_tax_id', value === 'none' ? '' : value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Seleccionar" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">Sin impuesto</SelectItem>
                                                    {taxes.map((tax) => (
                                                        <SelectItem key={tax.id} value={tax.id.toString()}>
                                                            {tax.name} ({Number(tax.rate).toFixed(2)}%)
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.default_tax_id && (
                                                <p className="text-sm text-red-600 dark:text-red-400">{errors.default_tax_id}</p>
                                            )}
                                        </div>

                                        <div className="hidden items-end justify-center pb-2 text-2xl font-semibold text-yellow-600 md:flex">=</div>

                                        <div className="space-y-2">
                                            <Label htmlFor="price_total">Precio total *</Label>
                                            <Input
                                                id="price_total"
                                                type="text"
                                                inputMode="decimal"
                                                value={totalPriceDisplay}
                                                onChange={(event) => handleTotalPriceChange(event.target.value)}
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                    </div>

                                    {inventoryEnabled && (
                                        <div className="grid grid-cols-1 gap-4 border-t pt-6 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="minimum_quantity">Cantidad minima</Label>
                                                <Input
                                                    id="minimum_quantity"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.minimum_quantity}
                                                    onChange={(event) => setData('minimum_quantity', event.target.value)}
                                                    placeholder="0"
                                                />
                                                {errors.minimum_quantity && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.minimum_quantity}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="unit_cost">Costo inventario</Label>
                                                <Input
                                                    id="unit_cost"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={data.unit_cost}
                                                    onChange={(event) => setData('unit_cost', event.target.value)}
                                                    placeholder="0.00"
                                                />
                                                {errors.unit_cost && <p className="text-sm text-red-600 dark:text-red-400">{errors.unit_cost}</p>}
                                            </div>
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="description">Descripcion</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(event) => setData('description', event.target.value)}
                                            placeholder="Agrega una descripcion corta del producto"
                                            rows={4}
                                        />
                                        {errors.description && <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <Collapsible open={showAdvanced} onOpenChange={setShowAdvanced}>
                                    <CardHeader className="pb-4">
                                        <div className="flex items-center justify-between">
                                            <CardTitle>Opciones avanzadas</CardTitle>
                                            <CollapsibleTrigger asChild>
                                                <Button type="button" variant="ghost" size="icon" className="text-muted-foreground">
                                                    <ChevronDown className={cn('h-4 w-4 transition-transform', showAdvanced && 'rotate-180')} />
                                                </Button>
                                            </CollapsibleTrigger>
                                        </div>
                                    </CardHeader>
                                    <CollapsibleContent>
                                        <CardContent className="space-y-5">
                                            <div>
                                                <h3 className="text-xl font-semibold text-foreground">Detalle de inventario</h3>
                                                <p className="text-muted-foreground">
                                                    Distribuye y controla las cantidades de tus productos en diferentes almacenes.
                                                </p>
                                            </div>

                                            {data.product_type === 'service' ? (
                                                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                                    Los servicios no requieren detalle de inventario por almacenes.
                                                </div>
                                            ) : (
                                                <div className="space-y-3">
                                                    {workspace_stocks.map((workspace) => (
                                                        <div
                                                            key={workspace.workspace_id}
                                                            className="flex items-center justify-between rounded-lg border bg-background p-4"
                                                        >
                                                            <div className="flex items-center gap-4">
                                                                <div className="flex h-14 w-14 items-center justify-center rounded-md border border-yellow-500/60 bg-yellow-50/40 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-300">
                                                                    <Tag className="h-5 w-5" />
                                                                </div>
                                                                <div className="space-y-1">
                                                                    <p className="text-lg font-medium text-foreground">{workspace.workspace_name}</p>
                                                                    <p className="text-sm text-muted-foreground">
                                                                        Actual: {workspace.current_quantity} cantidad
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-3">
                                                                <div className="w-36 space-y-1">
                                                                    <Label
                                                                        htmlFor={`workspace_initial_quantity_${workspace.workspace_id}`}
                                                                        className="text-xs text-muted-foreground"
                                                                    >
                                                                        Cantidad inicial
                                                                    </Label>
                                                                    <Input
                                                                        id={`workspace_initial_quantity_${workspace.workspace_id}`}
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        value={
                                                                            data.workspace_initial_quantities[workspace.workspace_id.toString()] ?? ''
                                                                        }
                                                                        onChange={(event) =>
                                                                            handleWorkspaceInitialQuantityChange(
                                                                                workspace.workspace_id,
                                                                                event.target.value,
                                                                            )
                                                                        }
                                                                        placeholder="0"
                                                                    />
                                                                    {errors[`workspace_initial_quantities.${workspace.workspace_id}`] && (
                                                                        <p className="text-xs text-red-600 dark:text-red-400">
                                                                            {errors[`workspace_initial_quantities.${workspace.workspace_id}`]}
                                                                        </p>
                                                                    )}
                                                                </div>
                                                                <MoreVertical className="h-5 w-5 text-muted-foreground" />
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </CardContent>
                                    </CollapsibleContent>
                                </Collapsible>
                            </Card>
                        </div>

                        <div className="space-y-4">
                            <Card className="sticky top-6">
                                <CardContent className="p-0">
                                    <div className="flex h-72 items-center justify-center border-b bg-muted/20">
                                        <ImagePlus className="h-16 w-16 text-muted-foreground/30" />
                                    </div>

                                    <div className="space-y-5 p-6">
                                        <div>
                                            <h3 className="text-2xl font-semibold text-foreground">{data.name || 'Nuevo producto'}</h3>
                                            <p className="mt-1 text-4xl font-semibold tracking-tight text-yellow-600 dark:text-yellow-400">
                                                {formatCurrency(totalPrice)}
                                            </p>
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Base: {formatCurrency(basePrice)} · ITBIS: {formatCurrency(taxAmount)}
                                            </p>
                                        </div>

                                        <div className="space-y-4 border-t pt-4">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="text-sm font-medium text-foreground">Realizar control de cantidades</p>
                                                    <p className="text-xs text-muted-foreground">Activa para manejar cantidades por inventario.</p>
                                                </div>
                                                <SwitchControl
                                                    checked={data.track_stock && data.product_type === 'product'}
                                                    disabled={data.product_type === 'service'}
                                                    onChange={(checked) => setData('track_stock', checked)}
                                                />
                                            </div>

                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="text-sm font-medium text-foreground">Venta en negativo</p>
                                                    <p className="text-xs text-muted-foreground">Permite vender aun sin stock disponible.</p>
                                                </div>
                                                <SwitchControl
                                                    checked={data.allow_negative_stock && data.product_type === 'product'}
                                                    disabled={data.product_type === 'service'}
                                                    onChange={(checked) => setData('allow_negative_stock', checked)}
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-3 border-t pt-5">
                                            <Button
                                                type="submit"
                                                className="w-full bg-yellow-600 text-white hover:bg-yellow-700"
                                                disabled={processing}
                                            >
                                                <Save className="mr-2 h-4 w-4" />
                                                {processing ? 'Guardando...' : 'Guardar'}
                                            </Button>
                                            <Button type="button" variant="outline" asChild className="w-full">
                                                <Link href={index().url}>Cancelar</Link>
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
