import { ArrowLeftRight, Building2, Package } from 'lucide-react';
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
        title: 'Transferencia de inventario',
        href: '/stock-transfers',
    },
    {
        title: 'New Transfer',
        href: '/stock-transfers/create',
    },
];

interface Props {
    products: Product[];
    availableWorkspaces: Workspace[];
    workspace: Workspace;
}

interface FormData {
    product_id: string;
    from_workspace_id: string;
    to_workspace_id: string;
    quantity: string;
    note: string;
}

export default function StockTransfersCreate({ products, availableWorkspaces, workspace }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        product_id: '',
        from_workspace_id: workspace.id.toString(),
        to_workspace_id: '',
        quantity: '',
        note: '',
    });

    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/stock-transfers', {
            onSuccess: () => reset(),
        });
    };

    const handleProductChange = (productId: string) => {
        setData('product_id', productId);
        const product = products.find((p) => p.id.toString() === productId);
        setSelectedProduct(product || null);
    };

    const getAvailableStock = () => {
        if (!selectedProduct || !selectedProduct.stocks) return 0;
        const stock = selectedProduct.stocks.find((s) => s.workspace_id === workspace.id);
        return stock ? stock.quantity : 0;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Stock Transfer" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">New Stock Transfer</h1>
                            <p className="text-muted-foreground">Transfer stock from {workspace.name} to another workspace</p>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="max-w-2xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Transfer Details</CardTitle>
                                <CardDescription>Select a product and destination workspace for the transfer</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Product Selection */}
                                    <div className="space-y-2">
                                        <Label htmlFor="product_id">Product *</Label>
                                        <Select value={data.product_id} onValueChange={handleProductChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select a product with available stock" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {products.map((product) => {
                                                    const stock = product.stocks?.find((s) => s.workspace_id === workspace.id);
                                                    const availableQty = stock ? stock.quantity : 0;

                                                    return (
                                                        <SelectItem key={product.id} value={product.id.toString()}>
                                                            <div className="flex w-full items-center justify-between">
                                                                <div className="flex items-center space-x-2">
                                                                    <Package className="h-4 w-4" />
                                                                    <span>{product.name}</span>
                                                                    <span className="text-muted-foreground">({product.sku})</span>
                                                                </div>
                                                                <span className="text-sm text-muted-foreground">Stock: {availableQty}</span>
                                                            </div>
                                                        </SelectItem>
                                                    );
                                                })}
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
                                                    <p className="text-sm text-muted-foreground">Available Stock</p>
                                                    <p className="text-2xl font-bold">{getAvailableStock()}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* From Workspace (read-only) */}
                                    <div className="space-y-2">
                                        <Label>From Workspace</Label>
                                        <div className="flex items-center space-x-2 rounded-md bg-muted p-3">
                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium">{workspace.name}</span>
                                            <span className="text-sm text-muted-foreground">(Current)</span>
                                        </div>
                                    </div>

                                    {/* To Workspace */}
                                    <div className="space-y-2">
                                        <Label htmlFor="to_workspace_id">To Workspace *</Label>
                                        <Select value={data.to_workspace_id} onValueChange={(value) => setData('to_workspace_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select destination workspace" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {availableWorkspaces.map((ws) => (
                                                    <SelectItem key={ws.id} value={ws.id.toString()}>
                                                        <div className="flex items-center space-x-2">
                                                            <Building2 className="h-4 w-4" />
                                                            <span>{ws.name}</span>
                                                        </div>
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.to_workspace_id && <p className="text-sm text-destructive">{errors.to_workspace_id}</p>}
                                    </div>

                                    {/* Quantity */}
                                    <div className="space-y-2">
                                        <Label htmlFor="quantity">Quantity *</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max={getAvailableStock()}
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Enter quantity to transfer"
                                        />
                                        {selectedProduct && <p className="text-sm text-muted-foreground">Maximum available: {getAvailableStock()}</p>}
                                        {errors.quantity && <p className="text-sm text-destructive">{errors.quantity}</p>}
                                    </div>

                                    {/* Note */}
                                    <div className="space-y-2">
                                        <Label htmlFor="note">Note</Label>
                                        <Textarea
                                            id="note"
                                            value={data.note}
                                            onChange={(e) => setData('note', e.target.value)}
                                            placeholder="Optional note about this transfer"
                                            rows={3}
                                        />
                                        {errors.note && <p className="text-sm text-destructive">{errors.note}</p>}
                                    </div>

                                    {/* Transfer Summary */}
                                    {selectedProduct && data.quantity && data.to_workspace_id && (
                                        <div className="rounded-lg border bg-blue-50 p-4 dark:bg-blue-950/20">
                                            <h4 className="mb-2 flex items-center font-medium">
                                                <ArrowLeftRight className="mr-2 h-4 w-4" />
                                                Transfer Summary
                                            </h4>
                                            <div className="space-y-1 text-sm">
                                                <p>
                                                    <span className="text-muted-foreground">Product:</span> {selectedProduct.name}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">Quantity:</span> {data.quantity}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">From:</span> {workspace.name}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">To:</span>{' '}
                                                    {availableWorkspaces.find((w) => w.id.toString() === data.to_workspace_id)?.name}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex items-center justify-end space-x-2">
                                        <Button variant="outline" asChild>
                                            <Link href="/stock-transfers">Cancel</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Processing...' : 'Create Transfer'}
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
