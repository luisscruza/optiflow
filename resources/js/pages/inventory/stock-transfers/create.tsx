import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeftRight, Building2, Package } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Product, type ProductStock, type Workspace } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventario',
        href: '/inventory',
    },
    {
        title: 'Transferencias de inventario',
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
    initial_product_id?: number | null;
}

interface FormData {
    product_id: string;
    from_workspace_id: string;
    to_workspace_id: string;
    quantity: string;
    reference: string;
    notes: string;
}

export default function StockTransfersCreate({ products, availableWorkspaces, initial_product_id }: Props) {
    const page = usePage();
    const workspace = (page.props as { workspace?: { current?: Workspace } }).workspace;
    const currentWorkspace = workspace?.current;

    const { data, setData, post, processing, errors } = useForm<FormData>({
        product_id: initial_product_id ? initial_product_id.toString() : '',
        from_workspace_id: currentWorkspace?.id?.toString() ?? '',
        to_workspace_id: '',
        quantity: '',
        reference: '',
        notes: '',
    });

    const selectedProduct = products.find((product) => product.id.toString() === data.product_id) ?? null;

    const getCurrentWorkspaceStock = (product: Product | null): ProductStock | undefined => {
        if (!product) {
            return undefined;
        }

        return product.stocks?.find((stock) => stock.workspace_id === currentWorkspace?.id);
    };

    const availableStock = Number(getCurrentWorkspaceStock(selectedProduct)?.quantity ?? 0);
    const destinationWorkspace = availableWorkspaces.find((ws) => ws.id.toString() === data.to_workspace_id);

    const productOptions: SearchableSelectOption[] = products.map((product) => {
        const stock = Number(product.stocks?.[0]?.quantity ?? 0);

        return {
            value: product.id.toString(),
            label: `${product.name} (${product.sku}) · Stock ${stock}`,
        };
    });

    const workspaceOptions: SearchableSelectOption[] = availableWorkspaces.map((ws) => ({
        value: ws.id.toString(),
        label: ws.name,
    }));

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post('/stock-transfers');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva transferencia de inventario" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-semibold tracking-tight text-foreground">Transferencia de inventario</h1>
                        <p className="text-muted-foreground">Mueve existencias entre almacenes con un flujo rapido y controlado.</p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Detalle de la transferencia</CardTitle>
                            <CardDescription>Selecciona origen y destino para la transferencia.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Desde almacen</Label>
                                    <div className="flex h-10 items-center gap-2 rounded-md border bg-muted/40 px-3 text-sm">
                                        <Building2 className="h-4 w-4 text-muted-foreground" />
                                        <span>{currentWorkspace?.name ?? '-'}</span>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="to_workspace_id">Hacia almacen *</Label>
                                    <SearchableSelect
                                        options={workspaceOptions}
                                        value={data.to_workspace_id}
                                        onValueChange={(value) => setData('to_workspace_id', value)}
                                        placeholder="Buscar sucursal de destino..."
                                        searchPlaceholder="Escribe para buscar sucursal..."
                                        emptyText="No se encontro ninguna sucursal."
                                        triggerClassName="h-10"
                                    />
                                    {errors.to_workspace_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.to_workspace_id}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Producto y cantidad</CardTitle>
                            <CardDescription>Usa el buscador para seleccionar el producto y define la cantidad a mover.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="product_id">Producto *</Label>
                                <SearchableSelect
                                    options={productOptions}
                                    value={data.product_id}
                                    onValueChange={(value) => setData('product_id', value)}
                                    placeholder="Buscar producto con stock..."
                                    searchPlaceholder="Escribe para buscar producto..."
                                    emptyText="No se encontraron productos con stock."
                                    triggerClassName="h-10"
                                />
                                {errors.product_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.product_id}</p>}
                            </div>

                            {selectedProduct && (
                                <div className="rounded-lg border border-yellow-500/40 bg-yellow-50/50 p-4 dark:bg-yellow-900/10">
                                    <div className="flex items-center justify-between gap-4">
                                        <div>
                                            <p className="font-medium text-foreground">{selectedProduct.name}</p>
                                            <p className="text-sm text-muted-foreground">SKU: {selectedProduct.sku}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm text-muted-foreground">Stock disponible</p>
                                            <p className="text-2xl font-semibold text-foreground">{availableStock}</p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="quantity">Cantidad *</Label>
                                    <Input
                                        id="quantity"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max={availableStock}
                                        value={data.quantity}
                                        onChange={(event) => setData('quantity', event.target.value)}
                                        placeholder="Ingresa cantidad a transferir"
                                        required
                                    />
                                    {selectedProduct && <p className="text-xs text-muted-foreground">Maximo disponible: {availableStock}</p>}
                                    {errors.quantity && <p className="text-sm text-red-600 dark:text-red-400">{errors.quantity}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="reference">Referencia</Label>
                                    <Input
                                        id="reference"
                                        value={data.reference}
                                        onChange={(event) => setData('reference', event.target.value)}
                                        placeholder="Opcional: codigo o referencia"
                                    />
                                    {errors.reference && <p className="text-sm text-red-600 dark:text-red-400">{errors.reference}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="notes">Nota</Label>
                                    <Textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(event) => setData('notes', event.target.value)}
                                        placeholder="Opcional: agrega una nota de referencia"
                                        rows={3}
                                    />
                                    {errors.notes && <p className="text-sm text-red-600 dark:text-red-400">{errors.notes}</p>}
                                </div>
                            </div>

                            {selectedProduct && destinationWorkspace && data.quantity && (
                                <div className="rounded-lg border bg-background p-4">
                                    <h4 className="mb-2 flex items-center text-sm font-semibold text-foreground">
                                        <ArrowLeftRight className="mr-2 h-4 w-4" />
                                        Resumen
                                    </h4>
                                    <div className="grid grid-cols-1 gap-2 text-sm text-muted-foreground md:grid-cols-2">
                                        <p>
                                            <span className="font-medium text-foreground">Producto:</span> {selectedProduct.name}
                                        </p>
                                        <p>
                                            <span className="font-medium text-foreground">Cantidad:</span> {data.quantity}
                                        </p>
                                        <p>
                                            <span className="font-medium text-foreground">Desde:</span> {currentWorkspace?.name ?? '-'}
                                        </p>
                                        <p>
                                            <span className="font-medium text-foreground">Hacia:</span> {destinationWorkspace.name}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/stock-transfers">Cancelar</Link>
                        </Button>
                        <Button type="submit" className="bg-yellow-600 text-white hover:bg-yellow-700" disabled={processing}>
                            <Package className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar transferencia'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
