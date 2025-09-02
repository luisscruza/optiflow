import { Package, ArrowLeft, TrendingUp } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { type BreadcrumbItem, type Product, type Workspace } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory',
    href: '/inventory',
  },
  {
    title: 'Initial Stock',
    href: '/initial-stock',
  },
  {
    title: 'Set Initial Stock',
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
    const product = products.find(p => p.id.toString() === productId);
    setSelectedProduct(product || null);
  };

  const calculateTotalValue = () => {
    const quantity = parseFloat(data.quantity) || 0;
    const cost = parseFloat(data.unit_cost) || 0;
    return quantity * cost;
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Set Initial Stock" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center space-x-4">
          <Button variant="outline" size="sm" asChild>
            <Link href="/initial-stock">
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back
            </Link>
          </Button>
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Set Initial Stock</h1>
            <p className="text-muted-foreground">
              Set up initial stock levels for products in {workspace.name}
            </p>
          </div>
        </div>

        {/* Form */}
        <div className="max-w-2xl">
          <Card>
            <CardHeader>
              <CardTitle>Initial Stock Details</CardTitle>
              <CardDescription>
                Select a product and set its initial stock quantity
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                {/* Product Selection */}
                <div className="space-y-2">
                  <Label htmlFor="product_id">Product *</Label>
                  <Select 
                    value={data.product_id} 
                    onValueChange={handleProductChange}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select a product without stock" />
                    </SelectTrigger>
                    <SelectContent>
                      {products.length === 0 ? (
                        <div className="p-4 text-center text-muted-foreground">
                          <Package className="h-8 w-8 mx-auto mb-2" />
                          <p>No products available</p>
                          <p className="text-sm">All stock-tracked products already have stock set</p>
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
                  {errors.product_id && (
                    <p className="text-sm text-destructive">{errors.product_id}</p>
                  )}
                </div>

                {/* Product Details Display */}
                {selectedProduct && (
                  <div className="p-4 bg-muted rounded-lg">
                    <div className="flex items-center justify-between">
                      <div>
                        <p className="font-medium">{selectedProduct.name}</p>
                        <p className="text-sm text-muted-foreground">SKU: {selectedProduct.sku}</p>
                        {selectedProduct.description && (
                          <p className="text-sm text-muted-foreground mt-1">{selectedProduct.description}</p>
                        )}
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-muted-foreground">Price</p>
                        <p className="text-lg font-bold">
                          ${Number(selectedProduct.price).toFixed(2)}
                        </p>
                        {selectedProduct.cost && (
                          <p className="text-sm text-muted-foreground">
                            Cost: ${Number(selectedProduct.cost).toFixed(2)}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                )}

                {/* Quantity */}
                <div className="space-y-2">
                  <Label htmlFor="quantity">Initial Quantity *</Label>
                  <Input
                    id="quantity"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.quantity}
                    onChange={(e) => setData('quantity', e.target.value)}
                    placeholder="Enter initial stock quantity"
                  />
                  {errors.quantity && (
                    <p className="text-sm text-destructive">{errors.quantity}</p>
                  )}
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
                    placeholder="Cost per unit (optional)"
                  />
                  <p className="text-sm text-muted-foreground">
                    Optional: The cost you paid for each unit
                  </p>
                  {errors.unit_cost && (
                    <p className="text-sm text-destructive">{errors.unit_cost}</p>
                  )}
                </div>

                {/* Total Value Display */}
                {data.quantity && data.unit_cost && (
                  <div className="p-4 border rounded-lg bg-blue-50 dark:bg-blue-950/20">
                    <div className="flex items-center justify-between">
                      <span className="font-medium">Total Stock Value:</span>
                      <span className="text-lg font-bold">
                        ${calculateTotalValue().toFixed(2)}
                      </span>
                    </div>
                    <p className="text-sm text-muted-foreground mt-1">
                      {data.quantity} units × ${data.unit_cost} per unit
                    </p>
                  </div>
                )}

                {/* Note */}
                <div className="space-y-2">
                  <Label htmlFor="note">Note</Label>
                  <Textarea
                    id="note"
                    value={data.note}
                    onChange={(e) => setData('note', e.target.value)}
                    placeholder="Optional note about this initial stock setup"
                    rows={3}
                  />
                  {errors.note && (
                    <p className="text-sm text-destructive">{errors.note}</p>
                  )}
                </div>

                {/* Setup Summary */}
                {selectedProduct && data.quantity && (
                  <div className="p-4 border rounded-lg bg-green-50 dark:bg-green-950/20">
                    <h4 className="font-medium mb-2 flex items-center">
                      <TrendingUp className="h-4 w-4 mr-2" />
                      Initial Stock Summary
                    </h4>
                    <div className="space-y-1 text-sm">
                      <p>
                        <span className="text-muted-foreground">Product:</span> {selectedProduct.name}
                      </p>
                      <p>
                        <span className="text-muted-foreground">SKU:</span> {selectedProduct.sku}
                      </p>
                      <p>
                        <span className="text-muted-foreground">Quantity:</span> {data.quantity}
                      </p>
                      {data.unit_cost && (
                        <p>
                          <span className="text-muted-foreground">Unit Cost:</span> ${data.unit_cost}
                        </p>
                      )}
                      <p>
                        <span className="text-muted-foreground">Workspace:</span> {workspace.name}
                      </p>
                    </div>
                  </div>
                )}

                {/* Actions */}
                <div className="flex items-center justify-end space-x-2">
                  <Button variant="outline" asChild>
                    <Link href="/initial-stock">Cancel</Link>
                  </Button>
                  <Button type="submit" disabled={processing || products.length === 0}>
                    {processing ? 'Setting up...' : 'Set Initial Stock'}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          {/* Help Card */}
          <Card className="mt-6">
            <CardHeader>
              <CardTitle className="text-lg">What is Initial Stock?</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm text-muted-foreground">
                <p>
                  Initial stock is the starting quantity for a product in this workspace. 
                  This is typically used when:
                </p>
                <ul className="list-disc list-inside space-y-1 ml-4">
                  <li>Adding a new product to your inventory</li>
                  <li>Starting inventory tracking for an existing product</li>
                  <li>Importing existing stock into a new workspace</li>
                </ul>
                <p className="mt-3">
                  After setting initial stock, you can use stock adjustments and transfers 
                  to manage ongoing inventory changes.
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
