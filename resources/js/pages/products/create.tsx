import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { create, store, index } from '@/actions/App/Http/Controllers/ProductController';
import { type BreadcrumbItem, type Tax } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: index().url,
  },
  {
    title: 'Create Product',
    href: create().url,
  },
];

interface Props {
  taxes: Tax[];
}

export default function ProductsCreate({ taxes }: Props) {

    const defaultTax = taxes.find((tax) => tax.is_default);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    sku: '',
    description: '',
    price: '',
    cost: '',
    track_stock: false,
    default_tax_id: defaultTax ? defaultTax.id.toString() : 'none',
    initial_quantity: '',
    minimum_quantity: '',
    unit_cost: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(store().url, {
      onSuccess: () => reset(),
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Create Product" />
      
      <div className="max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Create Product
            </h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Add a new product to your catalog.
            </p>
          </div>
          <Button variant="outline" asChild>
            <Link href={index().url}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Productos
            </Link>
          </Button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>
                The fundamental details about your product.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="name">Product Name *</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Enter product name"
                    required
                  />
                  {errors.name && (
                    <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="sku">SKU *</Label>
                  <Input
                    id="sku"
                    value={data.sku}
                    onChange={(e) => setData('sku', e.target.value)}
                    placeholder="e.g., PROD-001"
                    required
                  />
                  {errors.sku && (
                    <p className="text-sm text-red-600 dark:text-red-400">{errors.sku}</p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  placeholder="Enter product description"
                  rows={3}
                />
                {errors.description && (
                  <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Pricing</CardTitle>
              <CardDescription>
                Set the pricing information for this product.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="price">Sale Price *</Label>
                  <Input
                    id="price"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.price}
                    onChange={(e) => setData('price', e.target.value)}
                    placeholder="0.00"
                    required
                  />
                  {errors.price && (
                    <p className="text-sm text-red-600 dark:text-red-400">{errors.price}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="cost">Cost Price</Label>
                  <Input
                    id="cost"
                    type="number"
                    step="0.01"
                    min="0"
                    value={data.cost}
                    onChange={(e) => {
                      const newCost = e.target.value;
                      
                      // If stock tracking is enabled and unit cost is empty or matches current cost,
                      // inherit the new cost as unit cost
                      if (data.track_stock && (!data.unit_cost || data.unit_cost === data.cost)) {
                        setData(prevData => ({
                          ...prevData,
                          cost: newCost,
                          unit_cost: newCost,
                        }));
                      } else {
                        setData('cost', newCost);
                      }
                    }}
                    placeholder="0.00"
                  />
                  {errors.cost && (
                    <p className="text-sm text-red-600 dark:text-red-400">{errors.cost}</p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="default_tax_id">Default Tax</Label>
                <Select value={data.default_tax_id} onValueChange={(value) => setData('default_tax_id', value === 'none' ? '' : value)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a tax rate" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">No Tax</SelectItem>
                    {taxes.map((tax) => (
                      <SelectItem key={tax.id} value={tax.id.toString()}>
                        {tax.name} ({tax.rate}%)
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.default_tax_id && (
                  <p className="text-sm text-red-600 dark:text-red-400">{errors.default_tax_id}</p>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Inventario Settings</CardTitle>
              <CardDescription>
                Configure how inventory is tracked for this product.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="track_stock"
                  checked={data.track_stock}
                  onCheckedChange={(checked) => {
                    // When enabling stock tracking, inherit unit cost from cost price if available
                    if (checked && data.cost && !data.unit_cost) {
                      setData(prevData => ({
                        ...prevData,
                        track_stock: checked as boolean,
                        unit_cost: data.cost,
                      }));
                    } else {
                      setData('track_stock', checked as boolean);
                    }
                  }}
                />
                <div className="grid gap-1.5 leading-none">
                  <Label htmlFor="track_stock" className="text-sm font-medium">
                    Track inventory for this product
                  </Label>
                  <p className="text-xs text-muted-foreground">
                    Enable this to track stock levels and receive low stock alerts.
                  </p>
                </div>
              </div>
              {errors.track_stock && (
                <p className="text-sm text-red-600 dark:text-red-400 mt-2">{errors.track_stock}</p>
              )}

              {data.track_stock && (
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t">
                  <div className="space-y-2">
                    <Label htmlFor="initial_quantity">Initial Quantity</Label>
                    <Input
                      id="initial_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.initial_quantity}
                      onChange={(e) => setData('initial_quantity', e.target.value)}
                      placeholder="0"
                    />
                    {errors.initial_quantity && (
                      <p className="text-sm text-red-600 dark:text-red-400">{errors.initial_quantity}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="minimum_quantity">Minimum Quantity</Label>
                    <Input
                      id="minimum_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.minimum_quantity}
                      onChange={(e) => setData('minimum_quantity', e.target.value)}
                      placeholder="5"
                    />
                    {errors.minimum_quantity && (
                      <p className="text-sm text-red-600 dark:text-red-400">{errors.minimum_quantity}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="unit_cost">Unit Cost</Label>
                    <Input
                      id="unit_cost"
                      type="number"
                      step="0.01"
                      min="0"
                      value={data.unit_cost}
                      onChange={(e) => setData('unit_cost', e.target.value)}
                      placeholder="0.00"
                    />
                    <p className="text-xs text-muted-foreground">
                      Defaults to cost price when available
                    </p>
                    {errors.unit_cost && (
                      <p className="text-sm text-red-600 dark:text-red-400">{errors.unit_cost}</p>
                    )}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          <div className="flex items-center justify-end space-x-4">
            <Button type="button" variant="outline" asChild>
              <Link href={index().url}>
                Cancel
              </Link>
            </Button>
            <Button type="submit" disabled={processing}>
              <Save className="h-4 w-4 mr-2" />
              {processing ? 'Creating...' : 'Create Product'}
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
