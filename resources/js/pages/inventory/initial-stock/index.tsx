import { Package, Plus, Search, Eye, Calendar, TrendingUp } from 'lucide-react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { type BreadcrumbItem, type PaginatedProducts, type Workspace } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory',
    href: '/inventory',
  },
  {
    title: 'Initial Stock',
    href: '/initial-stock',
  },
];

interface Props {
  productsWithStock: PaginatedProducts;
  workspace: Workspace;
}

export default function InitialStockIndex({ productsWithStock, workspace }: Props) {
  const [search, setSearch] = useState('');

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get('/initial-stock', 
      { search: value || undefined },
      { preserveState: true, replace: true }
    );
  };

  const formatQuantity = (quantity: number) => {
    return Number(quantity).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  };

  const getStockStatus = (product: any) => {
    const stock = product.stocks?.[0];
    if (!stock) {
      return { label: 'No Stock Set', variant: 'secondary' as const };
    }
    
    if (stock.quantity <= 0) {
      return { label: 'Out of Stock', variant: 'destructive' as const };
    }
    if (stock.quantity <= stock.minimum_quantity) {
      return { label: 'Low Stock', variant: 'outline' as const };
    }
    return { label: 'In Stock', variant: 'default' as const };
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Initial Stock Setup" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Initial Stock Setup</h1>
            <p className="text-muted-foreground">
              Set up and manage initial stock levels for products in {workspace.name}
            </p>
          </div>

          <Button asChild>
            <Link href="/initial-stock/create">
              <Plus className="mr-2 h-4 w-4" />
              Set Initial Stock
            </Link>
          </Button>
        </div>

        {/* Products with Stock Status */}
        <Card>
          <CardHeader>
            <CardTitle>Product Stock Status</CardTitle>
            <CardDescription>
              Overview of stock-tracked products and their current status
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center space-x-4 mb-6">
              <div className="relative flex-1">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search products..."
                  value={search}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="pl-8"
                />
              </div>
            </div>

            {/* Products Table */}
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>SKU</TableHead>
                    <TableHead className="text-right">Current Stock</TableHead>
                    <TableHead className="text-right">Minimum</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Last Updated</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {productsWithStock.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8">
                        <div className="flex flex-col items-center space-y-2">
                          <Package className="h-8 w-8 text-muted-foreground" />
                          <p className="text-muted-foreground">No products found</p>
                          <Button asChild size="sm" variant="outline">
                            <Link href="/products/create">Add your first product</Link>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ) : (
                    productsWithStock.data.map((product) => {
                      const status = getStockStatus(product);
                      const stock = product.stocks?.[0];
                      
                      return (
                        <TableRow key={product.id}>
                          <TableCell className="font-medium">
                            <div className="flex items-center space-x-2">
                              <Package className="h-4 w-4 text-muted-foreground" />
                              <span>{product.name}</span>
                            </div>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            {product.sku}
                          </TableCell>
                          <TableCell className="text-right font-mono">
                            {stock ? formatQuantity(stock.quantity) : '-'}
                          </TableCell>
                          <TableCell className="text-right font-mono text-muted-foreground">
                            {stock ? formatQuantity(stock.minimum_quantity) : '-'}
                          </TableCell>
                          <TableCell>
                            <Badge variant={status.variant}>
                              {status.label}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            <div className="flex items-center space-x-1">
                              <Calendar className="h-3 w-3" />
                              <span className="text-sm">
                                {stock ? new Date(stock.updated_at).toLocaleDateString() : '-'}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            <div className="flex items-center justify-end space-x-2">
                              {stock ? (
                                <Button asChild size="sm" variant="outline">
                                  <Link href={`/initial-stock/${product.id}`}>
                                    <Eye className="h-3 w-3 mr-1" />
                                    View Details
                                  </Link>
                                </Button>
                              ) : (
                                <Button asChild size="sm">
                                  <Link href="/initial-stock/create">
                                    <TrendingUp className="h-3 w-3 mr-1" />
                                    Set Initial Stock
                                  </Link>
                                </Button>
                              )}
                            </div>
                          </TableCell>
                        </TableRow>
                      );
                    })
                  )}
                </TableBody>
              </Table>
            </div>

            {/* Pagination */}
            {productsWithStock.last_page > 1 && (
              <div className="flex items-center justify-between space-x-2 py-4">
                <div className="text-sm text-muted-foreground">
                  Showing {productsWithStock.from} to {productsWithStock.to} of{' '}
                  {productsWithStock.total} results
                </div>
                <div className="flex space-x-2">
                  {productsWithStock.links.prev && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(productsWithStock.links.prev!)}
                    >
                      Previous
                    </Button>
                  )}
                  {productsWithStock.links.next && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(productsWithStock.links.next!)}
                    >
                      Next
                    </Button>
                  )}
                </div>
              </div>
            )}
          </CardContent>
        </Card>
        </div>
      </div>
    </AppLayout>
  );
}
