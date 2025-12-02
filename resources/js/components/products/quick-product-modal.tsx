import { Form } from '@inertiajs/react';
import { PlusCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAdvancedForm?: () => void;
    onSuccess?: (product: any) => void;
}

export default function QuickProductModal({ open, onOpenChange, onAdvancedForm, onSuccess }: Props) {
    const nameRef = useRef<HTMLInputElement | null>(null);
    const [trackStock, setTrackStock] = useState(false);

    useEffect(() => {
        if (!open) {
            setTrackStock(false);
        }
    }, [open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center justify-between">Crear producto rápido</DialogTitle>
                </DialogHeader>

                <Form
                    action="/products/quick-create"
                    method="post"
                    resetOnSuccess
                    options={{
                        preserveScroll: true,
                        preserveState: true,
                    }}
                    onSuccess={(page: any) => {
                        onOpenChange(false);
                        if (onSuccess) {
                            const product = page.props.newlyCreatedProduct;
                            onSuccess(product);
                        }
                    }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre *</Label>
                                <Input ref={nameRef} id="name" name="name" placeholder="Nombre del producto" required />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="sku">SKU</Label>
                                    <Input id="sku" name="sku" placeholder="PROD-001" />
                                    {errors.sku && <p className="text-sm text-red-600">{errors.sku}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="price">Precio</Label>
                                    <Input id="price" name="price" type="number" step="0.01" min="0" placeholder="0.00" />
                                    {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="cost">Costo</Label>
                                    <Input id="cost" name="cost" type="number" step="0.01" min="0" placeholder="0.00" />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="track_stock">Rastrear inventario</Label>
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="track_stock"
                                            name="track_stock"
                                            checked={trackStock}
                                            onCheckedChange={(checked) => setTrackStock(checked as boolean)}
                                        />
                                        <span className="text-sm text-muted-foreground">Activar para gestionar stock</span>
                                    </div>
                                </div>
                            </div>

                            {trackStock && (
                                <div className="grid grid-cols-3 gap-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="initial_quantity">Cantidad inicial</Label>
                                        <Input id="initial_quantity" name="initial_quantity" type="number" step="1" min="0" placeholder="0" />
                                        {errors.initial_quantity && <p className="text-sm text-red-600">{errors.initial_quantity}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="minimum_quantity">Cantidad mínima</Label>
                                        <Input id="minimum_quantity" name="minimum_quantity" type="number" step="1" min="0" placeholder="5" />
                                        {errors.minimum_quantity && <p className="text-sm text-red-600">{errors.minimum_quantity}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="unit_cost">Costo unitario</Label>
                                        <Input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0" placeholder="0.00" />
                                        {errors.unit_cost && <p className="text-sm text-red-600">{errors.unit_cost}</p>}
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Input id="description" name="description" placeholder="Descripción corta" />
                            </div>

                            <div className="flex items-center justify-between pt-4">
                                <div className="flex items-center gap-2">
                                    <Button type="button" variant="ghost" onClick={() => onOpenChange(false)}>
                                        Cancelar
                                    </Button>
                                    <Button type="button" variant="link" onClick={() => onAdvancedForm && onAdvancedForm()}>
                                        Formulario completo
                                    </Button>
                                </div>

                                <Button type="submit" disabled={processing} className="flex items-center gap-2">
                                    <PlusCircle className="h-4 w-4" />
                                    {processing ? 'Creando...' : 'Crear producto'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
