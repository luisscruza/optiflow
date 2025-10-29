import { Package } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Product, type Workspace } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '#',
    },
    {
        title: 'Ajuste de inventario',
        href: '/stock-adjustments',
    },
    {
        title: 'Nuevo Ajuste',
        href: '/stock-adjustments/create',
    },
];

interface Props {
    products: Product[];
    workspace: Workspace;
}

interface FormData {
    product_id: string;
    adjustment_type: string;
    quantity: string;
    reason: string;
    reference: string;
    unit_cost: string;
}

export default function StockAdjustmentsCreate({ products, workspace }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        product_id: '',
        adjustment_type: '',
        quantity: '',
        reason: '',
        reference: '',
        unit_cost: '',
    });

    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/stock-adjustments', {
            onSuccess: () => reset(),
        });
    };

    const handleProductChange = (productId: string) => {
        setData('product_id', productId);
        const product = products.find((p) => p.id.toString() === productId);
        setSelectedProduct(product || null);
    };

    const adjustmentTypes = [
        { value: 'set_quantity', label: 'Establecer cantidad', description: 'Establecer la cantidad exacta de stock' },
        { value: 'add_quantity', label: 'Agregar cantidad', description: 'Agregar al stock actual' },
        { value: 'remove_quantity', label: 'Remover cantidad', description: 'Remover del stock actual' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo ajuste de Inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Nuevo Ajuste de Inventario</h1>
                            <p className="text-muted-foreground">Ajustar niveles de stock para productos en {workspace.name}</p>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="max-w-2xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles del ajuste</CardTitle>
                                <CardDescription>Selecciona un producto y especifica el tipo y cantidad del ajuste</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Product Selection */}
                                    <div className="space-y-2">
                                        <Label htmlFor="product_id">Producto *</Label>
                                        <Select value={data.product_id} onValueChange={handleProductChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un producto" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {products.map((product) => (
                                                    <SelectItem key={product.id} value={product.id.toString()}>
                                                        <div className="flex items-center space-x-2">
                                                            <Package className="h-4 w-4" />
                                                            <span>{product.name}</span>
                                                            <span className="text-muted-foreground">({product.sku})</span>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.product_id && <p className="text-sm text-destructive">{errors.product_id}</p>}
                                    </div>

                                    {/* Current Stock Display */}
                                    {selectedProduct && (
                                        <div className="rounded-lg bg-muted p-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium">{selectedProduct.name}</p>
                                                    <p className="text-sm text-muted-foreground">SKU: {selectedProduct.sku}</p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm text-muted-foreground">Stock actual</p>
                                                    <p className="text-2xl font-bold">{selectedProduct.stock_in_current_workspace?.quantity || 0}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Adjustment Type */}
                                    <div className="space-y-2">
                                        <Label htmlFor="adjustment_type">Tipo de ajuste *</Label>
                                        <Select value={data.adjustment_type} onValueChange={(value) => setData('adjustment_type', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona tipo de ajuste" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {adjustmentTypes.map((type) => (
                                                    <SelectItem key={type.value} value={type.value}>
                                                        <div>
                                                            <div className="font-medium">{type.label}</div>
                                                            <div className="text-sm text-muted-foreground">{type.description}</div>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.adjustment_type && <p className="text-sm text-destructive">{errors.adjustment_type}</p>}
                                    </div>

                                    {/* Quantity */}
                                    <div className="space-y-2">
                                        <Label htmlFor="quantity">Cantidad *</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Ingresa cantidad"
                                        />
                                        {errors.quantity && <p className="text-sm text-destructive">{errors.quantity}</p>}
                                    </div>

                                    {/* Unit Cost */}
                                    <div className="space-y-2">
                                        <Label htmlFor="unit_cost">Costo unitario</Label>
                                        <Input
                                            id="unit_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.unit_cost}
                                            onChange={(e) => setData('unit_cost', e.target.value)}
                                            placeholder="Costo unitario opcional"
                                        />
                                        {errors.unit_cost && <p className="text-sm text-destructive">{errors.unit_cost}</p>}
                                    </div>

                                    {/* Reference */}
                                    <div className="space-y-2">
                                        <Label htmlFor="reference">Referencia</Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) => setData('reference', e.target.value)}
                                            placeholder="Referencia opcional (ej. PO#, factura#)"
                                            maxLength={100}
                                        />
                                        {errors.reference && <p className="text-sm text-destructive">{errors.reference}</p>}
                                    </div>

                                    {/* Reason */}
                                    <div className="space-y-2">
                                        <Label htmlFor="reason">Razón *</Label>
                                        <Textarea
                                            id="reason"
                                            value={data.reason}
                                            onChange={(e) => setData('reason', e.target.value)}
                                            placeholder="Explica la razón de este ajuste"
                                            rows={3}
                                            maxLength={500}
                                        />
                                        {errors.reason && <p className="text-sm text-destructive">{errors.reason}</p>}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center justify-end space-x-2">
                                        <Button variant="outline" asChild>
                                            <Link href="/stock-adjustments">Cancelar</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Procesando...' : 'Crear ajuste'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
