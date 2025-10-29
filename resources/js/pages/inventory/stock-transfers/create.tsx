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
        title: 'Nueva transferencia',
        href: '/stock-transfers/create',
    },
];

interface Props {
    products: Product[];
    availableWorkspaces: Workspace[];
    workspace: {
        current: Workspace;
        available: Workspace[];
    };
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
        from_workspace_id: workspace?.current?.id?.toString(),
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
        console.log('=== getAvailableStock DEBUG ===');
        console.log('selectedProduct:', selectedProduct);
        console.log('workspace:', workspace);
        console.log('workspace.current.id:', workspace?.current?.id);
        console.log('workspace.current.id type:', typeof workspace?.current?.id);
        
        if (!selectedProduct || !selectedProduct.stocks) {
            console.log('No selectedProduct or stocks');
            return 0;
        }
        
        console.log('selectedProduct.stocks:', selectedProduct.stocks);
        
        selectedProduct.stocks.forEach((s, index) => {
            console.log(`Stock ${index}:`, s);
            console.log(`  workspace_id: ${s.workspace_id} (type: ${typeof s.workspace_id})`);
            console.log(`  quantity: ${s.quantity}`);
            console.log(`  Match with workspace.current.id (${workspace.current.id})?`, s.workspace_id === Number(workspace.current.id));
        });
        
        const stock = selectedProduct.stocks.find((s) => s.workspace_id === Number(workspace.current.id));
        console.log('Found stock:', stock);
        const result = stock ? Number(stock.quantity) : 0;
        console.log('Result:', result);
        return result;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva transferencia de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Nueva transferencia de inventario</h1>
                                                                    <p className="text-muted-foreground">Transfiere stock desde {workspace.current.name} a otro espacio de trabajo</p>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="max-w-2xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles de la transferencia</CardTitle>
                                <CardDescription>Selecciona un producto y el espacio de trabajo de destino para la transferencia</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Product Selection */}
                                    <div className="space-y-2">
                                        <Label htmlFor="product_id">Producto *</Label>
                                        <Select value={data.product_id} onValueChange={handleProductChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un producto con stock disponible" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {products.map((product) => {
                                                    console.log('=== Product in dropdown ===');
                                                    console.log('Product:', product);
                                                    console.log('Product.stocks:', product.stocks);
                                                    console.log('workspace.current.id:', workspace?.current?.id, 'type:', typeof workspace?.current?.id);
                                                    
                                                    const stock = product.stocks?.find((s) => {
                                                        console.log(`Comparing: ${s.workspace_id} (${typeof s.workspace_id}) === ${Number(workspace.current.id)} (number)`);
                                                        return s.workspace_id === Number(workspace.current.id);
                                                    });
                                                    console.log('Found stock for product:', stock);
                                                    
                                                    const availableQty = stock ? Number(stock.quantity) : 0;
                                                    console.log('Available qty:', availableQty);

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
                                                    <p className="text-sm text-muted-foreground">Stock disponible</p>
                                                    <p className="text-2xl font-bold">{getAvailableStock()}</p>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* From Workspace (read-only) */}
                                    <div className="space-y-2">
                                        <Label>Desde espacio de trabajo</Label>
                                        <div className="flex items-center space-x-2 rounded-md bg-muted p-3">
                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium">{workspace.current.name}</span>
                                            <span className="text-sm text-muted-foreground">(Actual)</span>
                                        </div>
                                    </div>

                                    {/* To Workspace */}
                                    <div className="space-y-2">
                                        <Label htmlFor="to_workspace_id">Al espacio de trabajo *</Label>
                                        <Select value={data.to_workspace_id} onValueChange={(value) => setData('to_workspace_id', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona el espacio de trabajo de destino" />
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
                                        <Label htmlFor="quantity">Cantidad *</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max={getAvailableStock()}
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Ingresa la cantidad a transferir"
                                        />
                                        {selectedProduct && <p className="text-sm text-muted-foreground">Máximo disponible: {getAvailableStock()}</p>}
                                        {errors.quantity && <p className="text-sm text-destructive">{errors.quantity}</p>}
                                    </div>

                                    {/* Note */}
                                    <div className="space-y-2">
                                        <Label htmlFor="note">Nota</Label>
                                        <Textarea
                                            id="note"
                                            value={data.note}
                                            onChange={(e) => setData('note', e.target.value)}
                                            placeholder="Nota opcional sobre esta transferencia"
                                            rows={3}
                                        />
                                        {errors.note && <p className="text-sm text-destructive">{errors.note}</p>}
                                    </div>

                                    {/* Transfer Summary */}
                                    {selectedProduct && data.quantity && data.to_workspace_id && (
                                        <div className="rounded-lg border bg-background p-4 dark:bg-blue-950/20">
                                            <h4 className="mb-2 flex items-center font-medium">
                                                <ArrowLeftRight className="mr-2 h-4 w-4" />
                                                Resumen de la transferencia
                                            </h4>
                                            <div className="space-y-1 text-sm">
                                                <p>
                                                    <span className="text-muted-foreground">Producto:</span> {selectedProduct.name}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">Cantidad:</span> {data.quantity}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">Desde:</span> {workspace.current.name}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">Hacia:</span>{' '}
                                                    {availableWorkspaces.find((w) => w.id.toString() === data.to_workspace_id)?.name}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex items-center justify-end space-x-2">
                                        <Button variant="outline" asChild>
                                            <Link href="/stock-transfers">Cancelar</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Procesando...' : 'Crear transferencia'}
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
