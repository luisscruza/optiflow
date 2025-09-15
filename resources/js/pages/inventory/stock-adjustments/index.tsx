import { Package, Plus, Search, Eye, Calendar, User } from 'lucide-react';
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
import { type BreadcrumbItem, type PaginatedStockAdjustments, type Workspace } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventario',
    href: '#',
  },
  {
    title: 'Ajuste de inventario',
    href: '/stock-adjustments',
  },
];

interface Props {
  stockAdjustments: PaginatedStockAdjustments;
  workspace: Workspace;
}

export default function StockAdjustmentsIndex({ stockAdjustments, workspace }: Props) {
  const [search, setSearch] = useState('');

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get('/stock-adjustments', 
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

  const getStockStatus = (quantity: number, minimum: number) => {
    if (quantity <= 0) {
      return { label: 'Out of Stock', variant: 'destructive' as const };
    }
    if (quantity <= minimum) {
      return { label: 'Low Stock', variant: 'secondary' as const };
    }
    return { label: 'In Stock', variant: 'default' as const };
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Ajuste de inventario" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Ajuste de inventario</h1>
            <p className="text-muted-foreground">
              Manage and track stock level adjustments for your products
            </p>
          </div>

          <Button asChild>
            <Link href="/stock-adjustments/create">
              <Plus className="mr-2 h-4 w-4" />
              New Adjustment
            </Link>
          </Button>
        </div>

        {/* Filters */}
        <Card>
          <CardHeader>
            <CardTitle>Current Stock Levels</CardTitle>
            <CardDescription>
              Overview of product stock levels in {workspace.name}
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

            {/* Ajuste de inventario Table */}
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
                  {stockAdjustments.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8">
                        <div className="flex flex-col items-center space-y-2">
                          <Package className="h-8 w-8 text-muted-foreground" />
                          <p className="text-muted-foreground">No products with stock found</p>
                          <Button asChild size="sm" variant="outline">
                            <Link href="/products/create">Add your first product</Link>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ) : (
                    stockAdjustments.data.map((stock) => {
                      const status = getStockStatus(stock.quantity, stock.minimum_quantity);
                      
                      return (
                        <TableRow key={stock.id}>
                          <TableCell className="font-medium">
                            <div className="flex items-center space-x-2">
                              <Package className="h-4 w-4 text-muted-foreground" />
                              <span>{stock.product?.name}</span>
                            </div>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            {stock.product?.sku}
                          </TableCell>
                          <TableCell className="text-right font-mono">
                            {formatQuantity(stock.quantity)}
                          </TableCell>
                          <TableCell className="text-right font-mono text-muted-foreground">
                            {formatQuantity(stock.minimum_quantity)}
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
                                {new Date(stock.updated_at).toLocaleDateString()}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            <Button asChild size="sm" variant="outline">
                              <Link href={`/stock-adjustments/${stock.product?.id}`}>
                                <Eye className="h-3 w-3 mr-1" />
                                View History
                              </Link>
                            </Button>
                          </TableCell>
                        </TableRow>
                      );
                    })
                  )}
                </TableBody>
              </Table>
            </div>

            {/* Pagination */}
            {stockAdjustments.last_page > 1 && (
              <div className="flex items-center justify-between space-x-2 py-4">
                <div className="text-sm text-muted-foreground">
                  Showing {stockAdjustments.from} to {stockAdjustments.to} of{' '}
                  {stockAdjustments.total} results
                </div>
                <div className="flex space-x-2">
                  {stockAdjustments.links.prev && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(stockAdjustments.links.prev!)}
                    >
                      Previous
                    </Button>
                  )}
                  {stockAdjustments.links.next && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(stockAdjustments.links.next!)}
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
