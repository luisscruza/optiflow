import { ArrowLeft, Package } from 'lucide-react';
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
        title: 'New Adjustment',
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
        { value: 'set_quantity', label: 'Set Quantity', description: 'Set the exact stock quantity' },
        { value: 'add_quantity', label: 'Add Quantity', description: 'Add to current stock' },
        { value: 'remove_quantity', label: 'Remove Quantity', description: 'Remove from current stock' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Stock Adjustment" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/stock-adjustments">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">New Stock Adjustment</h1>
                            <p className="text-muted-foreground">Adjust stock levels for products in {workspace.name}</p>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="max-w-2xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Adjustment Details</CardTitle>
                                <CardDescription>Select a product and specify the type and amount of adjustment</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Product Selection */}
                                    <div className="space-y-2">
                                        <Label htmlFor="product_id">Product *</Label>
                                        <Select value={data.product_id} onValueChange={handleProductChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a product" />
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
                                                    <p className="text-sm text-muted-foreground">Current Stock</p>
                                                    <p className="text-2xl font-bold">{selectedProduct.stock_in_current_workspace?.quantity || 0}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Adjustment Type */}
                                    <div className="space-y-2">
                                        <Label htmlFor="adjustment_type">Adjustment Type *</Label>
                                        <Select value={data.adjustment_type} onValueChange={(value) => setData('adjustment_type', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select adjustment type" />
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
                                        <Label htmlFor="quantity">Quantity *</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Enter quantity"
                                        />
                                        {errors.quantity && <p className="text-sm text-destructive">{errors.quantity}</p>}
                                    </div>

                                    {/* Unit Cost */}
                                    <div className="space-y-2">
                                        <Label htmlFor="unit_cost">Unit Cost</Label>
                                        <Input
                                            id="unit_cost"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.unit_cost}
                                            onChange={(e) => setData('unit_cost', e.target.value)}
                                            placeholder="Optional unit cost"
                                        />
                                        {errors.unit_cost && <p className="text-sm text-destructive">{errors.unit_cost}</p>}
                                    </div>

                                    {/* Reference */}
                                    <div className="space-y-2">
                                        <Label htmlFor="reference">Reference</Label>
                                        <Input
                                            id="reference"
                                            value={data.reference}
                                            onChange={(e) => setData('reference', e.target.value)}
                                            placeholder="Optional reference (e.g. PO#, invoice#)"
                                            maxLength={100}
                                        />
                                        {errors.reference && <p className="text-sm text-destructive">{errors.reference}</p>}
                                    </div>

                                    {/* Reason */}
                                    <div className="space-y-2">
                                        <Label htmlFor="reason">Reason *</Label>
                                        <Textarea
                                            id="reason"
                                            value={data.reason}
                                            onChange={(e) => setData('reason', e.target.value)}
                                            placeholder="Explain the reason for this adjustment"
                                            rows={3}
                                            maxLength={500}
                                        />
                                        {errors.reason && <p className="text-sm text-destructive">{errors.reason}</p>}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center justify-end space-x-2">
                                        <Button variant="outline" asChild>
                                            <Link href="/stock-adjustments">Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Processing...' : 'Create Adjustment'}
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
