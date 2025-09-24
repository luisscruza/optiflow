import { Package, TrendingUp } from 'lucide-react';
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
        href: '/inventory',
    },
    {
        title: 'Inventario inicial',
        href: '/initial-stock',
    },
    {
        title: 'Establecer inventario inicial',
        href: '/initial-stock/create',
    },
];

interface Props {
    products: Product[];
    workspace: Workspace;
}

interface FormData {
    product_id: string;
    quantity: string;
    unit_cost: string;
    note: string;
}

export default function InitialStockCreate({ products, workspace }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        product_id: '',
        quantity: '',
        unit_cost: '',
        note: '',
    });

    const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/initial-stock', {
            onSuccess: () => reset(),
        });
    };

    const handleProductChange = (productId: string) => {
        setData('product_id', productId);
        const product = products.find((p) => p.id.toString() === productId);
        setSelectedProduct(product || null);
    };

    const calculateTotalValue = () => {
        const quantity = parseFloat(data.quantity) || 0;
        const cost = parseFloat(data.unit_cost) || 0;
        return quantity * cost;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Establecer inventario inicial" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="space-y-8">
                    {/* Header */}
                    <div className="flex items-center space-x-4">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Establecer inventario inicial</h1>
                            <p className="text-muted-foreground">Configura los niveles de inventario inicial para productos en {workspace.name}</p>
                        </div>
                    </div>

                    {/* Form */}
                    <div className="max-w-2xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detalles del inventario inicial</CardTitle>
                                    <CardDescription>Selecciona un producto y establece su cantidad de inventario inicial</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    {/* Product Selection */}
                                    <div className="space-y-2">
                                        <Label htmlFor="product_id">Producto *</Label>
                                        <Select value={data.product_id} onValueChange={handleProductChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un producto sin stock" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {products.length === 0 ? (
                                                        <div className="p-4 text-center text-muted-foreground">
                                                        <Package className="mx-auto mb-2 h-8 w-8" />
                                                        <p>No hay productos disponibles</p>
                                                        <p className="text-sm">Todos los productos con seguimiento de stock ya tienen inventario establecido</p>
                                                    </div>
                                                ) : (
                                                    products.map((product) => (
                                                        <SelectItem key={product.id} value={product.id.toString()}>
                                                            <div className="flex items-center space-x-2">
                                                                <Package className="h-4 w-4" />
                                                                <span>{product.name}</span>
                                                                <span className="text-muted-foreground">({product.sku})</span>
                                                            </div>
                                                        </SelectItem>
                                                    ))
                                                )}
                                            </SelectContent>
                                        </Select>
                                        {errors.product_id && <p className="text-sm text-destructive">{errors.product_id}</p>}
                                    </div>

                                    {/* Product Details Display */}
                                    {selectedProduct && (
                                        <div className="rounded-lg bg-muted p-4">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium">{selectedProduct.name}</p>
                                                    <p className="text-sm text-muted-foreground">SKU: {selectedProduct.sku}</p>
                                                    {selectedProduct.description && (
                                                        <p className="mt-1 text-sm text-muted-foreground">{selectedProduct.description}</p>
                                                    )}
                                                </div>
                                                <div className="text-right">
                                                    <p className="text-sm text-muted-foreground">Precio</p>
                                                    <p className="text-lg font-bold">${Number(selectedProduct.price).toFixed(2)}</p>
                                                    {selectedProduct.cost && (
                                                        <p className="text-sm text-muted-foreground">
                                                            Costo: ${Number(selectedProduct.cost).toFixed(2)}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Quantity */}
                                    <div className="space-y-2">
                                        <Label htmlFor="quantity">Cantidad inicial *</Label>
                                        <Input
                                            id="quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.quantity}
                                            onChange={(e) => setData('quantity', e.target.value)}
                                            placeholder="Ingresa la cantidad inicial"
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
                                            placeholder="Costo por unidad (opcional)"
                                        />
                                        <p className="text-sm text-muted-foreground">Opcional: El costo que pagaste por cada unidad</p>
                                        {errors.unit_cost && <p className="text-sm text-destructive">{errors.unit_cost}</p>}
                                    </div>

                                    {/* Total Value Display */}
                                    {data.quantity && data.unit_cost && (
                                        <div className="rounded-lg border bg-background p-4 dark:bg-blue-950/20">
                                            <div className="flex items-center justify-between">
                                                        <span className="font-medium">Valor total del stock:</span>
                                                        <span className="text-lg font-bold">${calculateTotalValue().toFixed(2)}</span>
                                            </div>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {data.quantity} unidades × ${data.unit_cost} por unidad
                                                    </p>
                                        </div>
                                    )}

                                    {/* Note */}
                                    <div className="space-y-2">
                                        <Label htmlFor="note">Nota</Label>
                                        <Textarea
                                            id="note"
                                            value={data.note}
                                            onChange={(e) => setData('note', e.target.value)}
                                            placeholder="Nota opcional sobre esta configuración de inventario inicial"
                                            rows={3}
                                        />
                                        {errors.note && <p className="text-sm text-destructive">{errors.note}</p>}
                                    </div>

                                    {/* Setup Summary */}
                                    {selectedProduct && data.quantity && (
                                        <div className="rounded-lg border bg-green-50 p-4 dark:bg-green-950/20">
                                            <h4 className="mb-2 flex items-center font-medium">
                                                <TrendingUp className="mr-2 h-4 w-4" />
                                                Resumen del inventario inicial
                                            </h4>
                                            <div className="space-y-1 text-sm">
                                                <p>
                                                    <span className="text-muted-foreground">Producto:</span> {selectedProduct.name}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">SKU:</span> {selectedProduct.sku}
                                                </p>
                                                <p>
                                                    <span className="text-muted-foreground">Cantidad:</span> {data.quantity}
                                                </p>
                                                {data.unit_cost && (
                                                    <p>
                                                        <span className="text-muted-foreground">Costo unitario:</span> ${data.unit_cost}
                                                    </p>
                                                )}
                                                <p>
                                                    <span className="text-muted-foreground">Espacio de trabajo:</span> {workspace.name}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex items-center justify-end space-x-2">
                                        <Button variant="outline" asChild>
                                            <Link href="/initial-stock">Cancelar</Link>
                                        </Button>
                                        <Button type="submit" disabled={processing || products.length === 0}>
                                            {processing ? 'Configurando...' : 'Establecer inventario inicial'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        {/* Help Card */}
                        <Card className="mt-6">
                            <CardHeader>
                                <CardTitle className="text-lg">¿Qué es inventario inicial?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2 text-sm text-muted-foreground">
                                        <p>Inventario inicial es la cantidad inicial para un producto en este espacio de trabajo. Normalmente se utiliza cuando:</p>
                                    <ul className="ml-4 list-inside list-disc space-y-1">
                                            <li>Agregas un nuevo producto a tu inventario</li>
                                            <li>Comienzas el rastreo de inventario para un producto existente</li>
                                            <li>Importas stock existente a un nuevo espacio de trabajo</li>
                                    </ul>
                                        <p className="mt-3">
                                            Después de establecer el inventario inicial, puedes usar ajustes de inventario y transferencias para gestionar los cambios continuos de inventario.
                                        </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
