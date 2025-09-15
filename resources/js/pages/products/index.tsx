import { Package, Plus, Search, Filter, AlertTriangle, MoreHorizontal, Eye, Edit, Trash2, RotateCcw, ArrowLeftRight, TrendingUp } from 'lucide-react';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Checkbox } from '@/components/ui/checkbox';
import { index, create, show, edit, destroy } from '@/actions/App/Http/Controllers/ProductController';
import { type BreadcrumbItem, type PaginatedProducts, type ProductFilters } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Productos',
    href: index().url,
  },
];

interface Props {
  products: PaginatedProducts;
  filters: ProductFilters;
}

export default function ProductsIndex({ products, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [trackStock, setTrackStock] = useState(filters.track_stock);
  const [lowStock, setLowStock] = useState(filters.low_stock || false);
  const [deletingProduct, setDeletingProduct] = useState<number | null>(null);

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get(index().url, 
      { search: value || undefined, track_stock: trackStock, low_stock: lowStock || undefined },
      { preserveState: true, replace: true }
    );
  };

  const handleFilterChange = () => {
    router.get(index().url,
      { search: search || undefined, track_stock: trackStock, low_stock: lowStock || undefined },
      { preserveState: true, replace: true }
    );
  };

  const handleDeleteProduct = (productId: number) => {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
      setDeletingProduct(productId);
      router.delete(destroy(productId).url, {
        onFinish: () => setDeletingProduct(null),
      });
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Productos" />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Productos
            </h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Manage your product catalog and inventory.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href="/stock-adjustments">
                <Package className="h-4 w-4 mr-2" />
                Stock Management
              </Link>
            </Button>
            <Button asChild>
              <Link href={create().url}>
                <Plus className="h-4 w-4 mr-2" />
                Add Product
              </Link>
            </Button>
          </div>
        </div>

        {/* Filters */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle className="text-lg">Search & Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex flex-col gap-4 md:flex-row md:items-end">
              <div className="flex-1">
                <label htmlFor="search" className="block text-sm font-medium mb-2">
                  Search Productos
                </label>
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <Input
                    id="search"
                    placeholder="Search by name, SKU, or description..."
                    value={search}
                    onChange={(e) => handleSearch(e.target.value)}
                    className="pl-10"
                  />
                </div>
              </div>
              <div className="flex gap-4 items-center">
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="track_stock"
                    checked={trackStock}
                    onCheckedChange={(checked) => {
                      setTrackStock(checked as boolean);
                      setTimeout(handleFilterChange, 0);
                    }}
                  />
                  <label htmlFor="track_stock" className="text-sm font-medium">
                    Track Stock Only
                  </label>
                </div>
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="low_stock"
                    checked={lowStock}
                    onCheckedChange={(checked) => {
                      setLowStock(checked as boolean);
                      setTimeout(handleFilterChange, 0);
                    }}
                  />
                  <label htmlFor="low_stock" className="text-sm font-medium">
                    Low Stock Only
                  </label>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Quick Inventario Actions */}
        <Card className="mb-6">
          <CardHeader>
            <CardTitle className="text-lg">Inventario</CardTitle>
            <CardDescription>
              Quick access to stock management features
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Button variant="outline" asChild className="h-auto p-4">
                <Link href="/stock-adjustments">
                  <div className="flex flex-col items-center space-y-2">
                    <RotateCcw className="h-6 w-6" />
                    <div className="text-center">
                      <div className="font-medium">Ajuste de inventario</div>
                      <div className="text-sm text-muted-foreground">Manage stock levels</div>
                    </div>
                  </div>
                </Link>
              </Button>
              <Button variant="outline" asChild className="h-auto p-4">
                <Link href="/stock-transfers">
                  <div className="flex flex-col items-center space-y-2">
                    <ArrowLeftRight className="h-6 w-6" />
                    <div className="text-center">
                      <div className="font-medium">Transferencia de inventario</div>
                      <div className="text-sm text-muted-foreground">Mover entre sucursales</div>
                    </div>
                  </div>
                </Link>
              </Button>
              <Button variant="outline" asChild className="h-auto p-4">
                <Link href="/initial-stock">
                  <div className="flex flex-col items-center space-y-2">
                    <TrendingUp className="h-6 w-6" />
                    <div className="text-center">
                      <div className="font-medium">Inventario inicial</div>
                      <div className="text-sm text-muted-foreground">Set up product stock</div>
                    </div>
                  </div>
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Productos Table */}
        {products.data.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center py-12">
              <Package className="h-12 w-12 text-gray-400 mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                No products found
              </h3>
              <p className="text-gray-600 dark:text-gray-400 text-center mb-6 max-w-md">
                {filters.search ? 
                  'No products match your search criteria. Try adjusting your filters.' :
                  'Get started by adding your first product to the catalog.'
                }
              </p>
              <Button asChild>
                <Link prefetch href={create().url}>
                  <Plus className="h-4 w-4 mr-2" />
                  Add Product
                </Link>
              </Button>
            </CardContent>
          </Card>
        ) : (
          <Card>
            <CardHeader>
              <CardTitle>
                Productos ({products.total})
              </CardTitle>
              <CardDescription>
                Showing {products.from} to {products.to} of {products.total} products
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>SKU</TableHead>
                    <TableHead>Price</TableHead>
                    <TableHead>Stock</TableHead>
                    <TableHead>Tax</TableHead>
                    <TableHead className="w-[70px]">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {products.data.map((product) => (
                    <TableRow key={product.id}>
                      <TableCell>
                        <div>
                          <div className="font-medium">{product.name}</div>
                          {product.description && (
                            <div className="text-sm text-gray-500 mt-1 line-clamp-2">
                              {product.description}
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        <code className="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm">
                          {product.sku}
                        </code>
                      </TableCell>
                      <TableCell>
                        <div>
                          <div className="font-medium">{formatCurrency(product.price)}</div>
                          {product.cost && (
                            <div className="text-sm text-gray-500">
                              Cost: {formatCurrency(product.cost)}
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        {product.track_stock ? (
                          product.stock_in_current_workspace ? (
                            <div className="flex items-center gap-2">
                              <span className="font-medium">
                                {product.stock_in_current_workspace.quantity}
                              </span>
                              {product.stock_in_current_workspace.quantity <= product.stock_in_current_workspace.minimum_quantity && (
                                <Badge variant="destructive" className="text-xs">
                                  <AlertTriangle className="h-3 w-3 mr-1" />
                                  Low
                                </Badge>
                              )}
                            </div>
                          ) : (
                            <Badge variant="secondary">No Stock Data</Badge>
                          )
                        ) : (
                          <Badge variant="outline">Not Tracked</Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        {product.default_tax ? (
                          <span className="text-sm">
                            {product.default_tax.name} ({product.default_tax.rate}%)
                          </span>
                        ) : (
                          <span className="text-gray-500">No Tax</span>
                        )}
                      </TableCell>
                      <TableCell>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                              <Link href={show(product.id).url}>
                                <Eye className="h-4 w-4 mr-2" />
                                View
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                              <Link href={edit(product.id).url}>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                              </Link>
                            </DropdownMenuItem>
                            {product.track_stock && (
                              <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                  <Link href={`/stock-adjustments/${product.id}`}>
                                    <RotateCcw className="h-4 w-4 mr-2" />
                                    Stock History
                                  </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                  <Link href="/stock-adjustments/create" preserveState={false}>
                                    <TrendingUp className="h-4 w-4 mr-2" />
                                    Adjust Stock
                                  </Link>
                                </DropdownMenuItem>
                                {!product.stock_in_current_workspace && (
                                  <DropdownMenuItem asChild>
                                    <Link href="/initial-stock/create" preserveState={false}>
                                      <Package className="h-4 w-4 mr-2" />
                                      Set Inventario inicial
                                    </Link>
                                  </DropdownMenuItem>
                                )}
                                <DropdownMenuItem asChild>
                                  <Link href="/stock-transfers/create" preserveState={false}>
                                    <ArrowLeftRight className="h-4 w-4 mr-2" />
                                    Transfer Stock
                                  </Link>
                                </DropdownMenuItem>
                              </>
                            )}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem 
                              onClick={() => handleDeleteProduct(product.id)}
                              className="text-red-600 dark:text-red-400"
                              disabled={deletingProduct === product.id}
                            >
                              <Trash2 className="h-4 w-4 mr-2" />
                              {deletingProduct === product.id ? 'Deleting...' : 'Delete'}
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {/* Pagination */}
              {products.last_page > 1 && (
                <div className="flex justify-between items-center mt-6">
                  <div className="text-sm text-gray-600 dark:text-gray-400">
                    Page {products.current_page} of {products.last_page}
                  </div>
                  <div className="flex gap-2">
                    {products.links.prev && (
                      <Button variant="outline" size="sm" asChild>
                        <Link href={products.links.prev}>
                          Previous
                        </Link>
                      </Button>
                    )}
                    {products.links.next && (
                      <Button variant="outline" size="sm" asChild>
                        <Link href={products.links.next}>
                          Next
                        </Link>
                      </Button>
                    )}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </div>
    </AppLayout>
  );
}
