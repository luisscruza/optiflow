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
import { edit, update, index, show } from '@/actions/App/Http/Controllers/ProductController';
import { type BreadcrumbItem, type Product, type Tax } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: index().url,
  },
  {
    title: 'Edit Product',
    href: '#',
  },
];

interface Props {
  product: Product;
  taxes: Tax[];
}

export default function ProductsEdit({ product, taxes }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    name: product.name,
    sku: product.sku,
    description: product.description || '',
    price: product.price.toString(),
    cost: product.cost?.toString() || '',
    track_stock: product.track_stock,
    default_tax_id: product.default_tax_id?.toString() || 'none',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(update(product.id).url);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Edit ${product.name}`} />
      
      <div className="max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Edit Product
            </h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Update the details for <strong>{product.name}</strong>
            </p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={show(product.id).url}>
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to Product
              </Link>
            </Button>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>
                Update the fundamental details about your product.
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
                Update the pricing information for this product.
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
                    onChange={(e) => setData('cost', e.target.value)}
                    placeholder="0.00"
                  />
                  {errors.cost && (
                    <p className="text-sm text-red-600 dark:text-red-400">{errors.cost}</p>
                  )}
                </div>
              </div>

              {data.cost && data.price && parseFloat(data.cost) > 0 && parseFloat(data.price) > 0 && (
                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-blue-800 dark:text-blue-200 font-medium">Profit:</span>
                      <span className="ml-2 font-semibold">
                        ${(parseFloat(data.price) - parseFloat(data.cost)).toFixed(2)}
                      </span>
                    </div>
                    <div>
                      <span className="text-blue-800 dark:text-blue-200 font-medium">Margin:</span>
                      <span className="ml-2 font-semibold">
                        {(((parseFloat(data.price) - parseFloat(data.cost)) / parseFloat(data.cost)) * 100).toFixed(2)}%
                      </span>
                    </div>
                  </div>
                </div>
              )}

              <div className="space-y-2">
                <Label htmlFor="default_tax_id">Default Tax</Label>
                <Select 
                  value={data.default_tax_id} 
                  onValueChange={(value) => setData('default_tax_id', value === 'none' ? '' : value)}
                >
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
            <CardContent>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="track_stock"
                  checked={data.track_stock}
                  onCheckedChange={(checked) => setData('track_stock', checked as boolean)}
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

              {!data.track_stock && product.track_stock && (
                <div className="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                  <div className="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                    <span className="text-sm font-medium">Warning</span>
                  </div>
                  <p className="text-xs text-yellow-600 dark:text-yellow-300 mt-1">
                    Disabling stock tracking will not delete existing stock records, but they won't be updated automatically.
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          <div className="flex items-center justify-end space-x-4">
            <Button type="button" variant="outline" asChild>
              <Link href={show(product.id).url}>
                Cancel
              </Link>
            </Button>
            <Button type="submit" disabled={processing}>
              <Save className="h-4 w-4 mr-2" />
              {processing ? 'Updating...' : 'Update Product'}
            </Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
