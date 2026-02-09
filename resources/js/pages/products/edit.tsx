import { Head, Link, useForm } from '@inertiajs/react';
import { Check, ChevronDown, ImagePlus, MoreHorizontal, Save, Tag } from 'lucide-react';
import { useState } from 'react';

import { index, show, update } from '@/actions/App/Http/Controllers/ProductController';
import { store as storeStockAdjustment } from '@/actions/App/Http/Controllers/StockAdjustmentController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Product, type Tax } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Productos',
        href: index().url,
    },
    {
        title: 'Editar producto',
        href: '#',
    },
];

interface Props {
    product: Product;
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

export default function ProductsEdit({ product, taxes, workspace_stocks }: Props) {
    const { format: formatCurrency } = useCurrency();
    const [showAdvanced, setShowAdvanced] = useState(true);
    const [isQuickStockDialogOpen, setIsQuickStockDialogOpen] = useState(false);
    const [selectedWorkspaceStock, setSelectedWorkspaceStock] = useState<Props['workspace_stocks'][number] | null>(null);

    const roundCurrency = (value: number): number => {
        return Math.round(value * 100) / 100;
    };

    const normalizeAmount = (value: string): string => {
        return value.replace(/,/g, '');
    };

    const { data, setData, put, processing, errors } = useForm({
        name: product.name,
        sku: product.sku,
        description: product.description || '',
        product_type: product.product_type || (product.track_stock ? 'product' : 'service'),
        price: product.price.toString(),
        cost: product.cost?.toString() || '',
        track_stock: product.track_stock,
        allow_negative_stock: product.allow_negative_stock ?? false,
        default_tax_id: product.default_tax_id?.toString() || '',
    });

    const {
        data: stockAdjustmentData,
        setData: setStockAdjustmentData,
        post: postStockAdjustment,
        processing: stockAdjustmentProcessing,
        errors: stockAdjustmentErrors,
        reset: resetStockAdjustment,
        clearErrors: clearStockAdjustmentErrors,
    } = useForm({
        product_id: product.id.toString(),
        workspace_id: '',
        adjustment_type: 'set_quantity',
        quantity: '',
        reason: 'Ajuste rapido desde la ficha del producto.',
        reference: '',
        redirect_back: true,
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
        put(update(product.id).url);
    };

    const closeQuickStockDialog = () => {
        setIsQuickStockDialogOpen(false);
        setSelectedWorkspaceStock(null);
        clearStockAdjustmentErrors();
        resetStockAdjustment('workspace_id', 'quantity', 'reference');
        setStockAdjustmentData('reason', 'Ajuste rapido desde la ficha del producto.');
    };

    const handleQuickStockAdjustment = (workspace: Props['workspace_stocks'][number]) => {
        setSelectedWorkspaceStock(workspace);
        clearStockAdjustmentErrors();
        setStockAdjustmentData((previous) => ({
            ...previous,
            workspace_id: workspace.workspace_id.toString(),
            quantity: workspace.current_quantity.toString(),
            reason: `Ajuste rapido en ${workspace.workspace_name}.`,
            reference: '',
        }));
        setIsQuickStockDialogOpen(true);
    };

    const handleQuickStockSubmit = (event: React.FormEvent) => {
        event.preventDefault();

        postStockAdjustment(storeStockAdjustment().url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeQuickStockDialog();
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

    const selectedTax = taxes.find((tax) => tax.id.toString() === data.default_tax_id);
    const taxRate = Number(selectedTax?.rate ?? 0);
    const basePrice = Number(data.price || 0);
    const taxAmount = roundCurrency(basePrice * (taxRate / 100));
    const totalPrice = roundCurrency(basePrice + taxAmount);
    const totalPriceDisplay = data.price === '' ? '' : totalPrice.toString();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${product.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-semibold tracking-tight text-foreground">Editar producto</h1>
                    <p className="mt-2 text-muted-foreground">Actualiza la ficha de {product.name} con una vista mas clara y util.</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                        <div className="space-y-6">
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle>Informacion general</CardTitle>
                                    <CardDescription>Define tipo, referencia y configuracion comercial del producto.</CardDescription>
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
                                        <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} required />
                                        {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="sku">Referencia (SKU) *</Label>
                                            <Input id="sku" value={data.sku} onChange={(event) => setData('sku', event.target.value)} required />
                                            {errors.sku && <p className="text-sm text-red-600 dark:text-red-400">{errors.sku}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="cost">Costo por unidad</Label>
                                            <Input
                                                id="cost"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={data.cost}
                                                onChange={(event) => setData('cost', event.target.value)}
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
                                                required
                                            />
                                        </div>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">Descripcion</Label>
                                        <Textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(event) => setData('description', event.target.value)}
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
                                                    Visualiza la existencia actual de este producto en cada almacen.
                                                </p>
                                            </div>

                                            {data.product_type === 'service' ? (
                                                <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                                                    Los servicios no requieren inventario por almacen.
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
                                                                <div>
                                                                    <p className="text-lg font-medium text-foreground">{workspace.workspace_name}</p>
                                                                    <p className="text-sm text-muted-foreground">
                                                                        {workspace.current_quantity} cantidad
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <DropdownMenu>
                                                                <DropdownMenuTrigger asChild>
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        className="h-9 w-9 text-muted-foreground"
                                                                    >
                                                                        <MoreHorizontal className="h-5 w-5" />
                                                                    </Button>
                                                                </DropdownMenuTrigger>
                                                                <DropdownMenuContent align="end">
                                                                    <DropdownMenuItem onClick={() => handleQuickStockAdjustment(workspace)}>
                                                                        Ajustar stock rapido
                                                                    </DropdownMenuItem>
                                                                </DropdownMenuContent>
                                                            </DropdownMenu>
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
                                            <h3 className="text-2xl font-semibold text-foreground">{data.name || product.name}</h3>
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
                                                    <p className="text-xs text-muted-foreground">
                                                        Activa para manejar inventario y alertas de stock.
                                                    </p>
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
                                                    <p className="text-xs text-muted-foreground">Permite vender aun sin disponibilidad.</p>
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
                                                {processing ? 'Guardando...' : 'Guardar cambios'}
                                            </Button>
                                            <Button type="button" variant="outline" asChild className="w-full">
                                                <Link href={show(product.id).url}>Cancelar</Link>
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </form>

                <Dialog open={isQuickStockDialogOpen} onOpenChange={(open) => (open ? setIsQuickStockDialogOpen(true) : closeQuickStockDialog())}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Seleccionar almacen</DialogTitle>
                            <DialogDescription>Ajusta el inventario de este producto sin guardar la edicion general.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleQuickStockSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="workspace_name">Almacen *</Label>
                                <Input id="workspace_name" value={selectedWorkspaceStock?.workspace_name ?? ''} disabled />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="workspace_quantity">Cantidad final *</Label>
                                    <Input
                                        id="workspace_quantity"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={stockAdjustmentData.quantity}
                                        onChange={(event) => setStockAdjustmentData('quantity', event.target.value)}
                                        required
                                    />
                                    {stockAdjustmentErrors.quantity && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{stockAdjustmentErrors.quantity}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="workspace_reason">Motivo *</Label>
                                    <Input
                                        id="workspace_reason"
                                        value={stockAdjustmentData.reason}
                                        onChange={(event) => setStockAdjustmentData('reason', event.target.value)}
                                        required
                                    />
                                    {stockAdjustmentErrors.reason && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{stockAdjustmentErrors.reason}</p>
                                    )}
                                </div>
                            </div>

                            {stockAdjustmentErrors.workspace_id && (
                                <p className="text-sm text-red-600 dark:text-red-400">{stockAdjustmentErrors.workspace_id}</p>
                            )}

                            <div className="flex items-center justify-end gap-2 pt-2">
                                <Button type="button" variant="outline" onClick={closeQuickStockDialog} disabled={stockAdjustmentProcessing}>
                                    Cancelar
                                </Button>
                                <Button type="submit" className="bg-yellow-600 text-white hover:bg-yellow-700" disabled={stockAdjustmentProcessing}>
                                    {stockAdjustmentProcessing ? 'Guardando...' : 'Guardar'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
