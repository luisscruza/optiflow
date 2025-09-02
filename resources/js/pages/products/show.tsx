import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2, Package, TrendingUp, DollarSign, BarChart3, RotateCcw, ArrowLeftRight } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { 
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, edit, destroy } from '@/actions/App/Http/Controllers/ProductController';
import { type BreadcrumbItem, type Product, type StockMovement } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Products',
    href: index().url,
  },
  {
    title: 'Product Details',
    href: '#',
  },
];

interface Props {
  product: Product & {
    stock_movements?: StockMovement[];
  };
}

export default function ProductsShow({ product }: Props) {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStockStatus = () => {
    if (!product.track_stock) {
      return { status: 'not_tracked', label: 'Not Tracked', variant: 'secondary' as const };
    }

    const stock = product.stock_in_current_workspace;
    if (!stock) {
      return { status: 'no_data', label: 'No Stock Data', variant: 'destructive' as const };
    }

    if (stock.quantity <= 0) {
      return { status: 'out_of_stock', label: 'Out of Stock', variant: 'destructive' as const };
    }

    if (stock.quantity <= stock.minimum_quantity) {
      return { status: 'low_stock', label: 'Low Stock', variant: 'outline' as const };
    }

    return { status: 'in_stock', label: 'In Stock', variant: 'default' as const };
  };

  const stockStatus = getStockStatus();

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`${product.name} - Product Details`} />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div className="flex items-center gap-4">
            <Button variant="outline" asChild>
              <Link href={index().url}>
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to Products
              </Link>
            </Button>
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                {product.name}
              </h1>
              <p className="text-gray-600 dark:text-gray-400">
                SKU: <code className="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm">{product.sku}</code>
              </p>
            </div>
          </div>
          <div className="flex gap-2">
            {product.track_stock && (
              <>
                <Button variant="outline" asChild>
                  <Link href={`/stock-adjustments/${product.id}`}>
                    <RotateCcw className="h-4 w-4 mr-2" />
                    Stock History
                  </Link>
                </Button>
                <Button variant="outline" asChild>
                  <Link href="/stock-adjustments/create">
                    <TrendingUp className="h-4 w-4 mr-2" />
                    Adjust Stock
                  </Link>
                </Button>
                {!product.stock_in_current_workspace && (
                  <Button variant="outline" asChild>
                    <Link href="/initial-stock/create">
                      <Package className="h-4 w-4 mr-2" />
                      Set Initial Stock
                    </Link>
                  </Button>
                )}
                <Button variant="outline" asChild>
                  <Link href="/stock-transfers/create">
                    <ArrowLeftRight className="h-4 w-4 mr-2" />
                    Transfer Stock
                  </Link>
                </Button>
              </>
            )}
            <Button asChild>
              <Link href={edit(product.id).url}>
                <Edit className="h-4 w-4 mr-2" />
                Edit Product
              </Link>
            </Button>
            <Button 
              variant="destructive" 
              onClick={() => {
                if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                  // Handle delete
                }
              }}
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Delete
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Basic Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Package className="h-5 w-5" />
                  Product Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <h3 className="font-medium text-gray-900 dark:text-white mb-2">Description</h3>
                  <p className="text-gray-600 dark:text-gray-400">
                    {product.description || 'No description provided.'}
                  </p>
                </div>
                
                <Separator />
                
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-medium text-gray-900 dark:text-white">Sale Price</h4>
                    <p className="text-lg font-semibold text-green-600 dark:text-green-400">
                      {formatCurrency(product.price)}
                    </p>
                  </div>
                  {product.cost && (
                    <div>
                      <h4 className="font-medium text-gray-900 dark:text-white">Cost Price</h4>
                      <p className="text-lg font-semibold text-gray-600 dark:text-gray-400">
                        {formatCurrency(product.cost)}
                      </p>
                    </div>
                  )}
                </div>

                {product.cost && (
                  <>
                    <Separator />
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Profit</h4>
                        <p className="text-lg font-semibold text-blue-600 dark:text-blue-400">
                          {formatCurrency(product.price - product.cost)}
                        </p>
                      </div>
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Profit Margin</h4>
                        <p className="text-lg font-semibold text-blue-600 dark:text-blue-400">
                          {(((product.price - product.cost) / product.cost) * 100).toFixed(2)}%
                        </p>
                      </div>
                    </div>
                  </>
                )}

                <Separator />
                
                <div>
                  <h4 className="font-medium text-gray-900 dark:text-white mb-2">Default Tax</h4>
                  {product.default_tax ? (
                    <Badge variant="outline">
                      {product.default_tax.name} ({product.default_tax.rate}%)
                    </Badge>
                  ) : (
                    <span className="text-gray-500">No tax configured</span>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Stock Movements */}
            {product.track_stock && product.stock_movements && product.stock_movements.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <BarChart3 className="h-5 w-5" />
                    Recent Stock Movements
                  </CardTitle>
                  <CardDescription>
                    Latest inventory transactions for this product
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Date</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>Quantity</TableHead>
                        <TableHead>Reference</TableHead>
                        <TableHead>Notes</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {product.stock_movements.slice(0, 10).map((movement) => (
                        <TableRow key={movement.id}>
                          <TableCell>{formatDate(movement.created_at)}</TableCell>
                          <TableCell>
                            <Badge variant={movement.type === 'in' ? 'default' : 'secondary'}>
                              {movement.type.toUpperCase()}
                            </Badge>
                          </TableCell>
                          <TableCell className={movement.type === 'in' ? 'text-green-600' : 'text-red-600'}>
                            {movement.type === 'in' ? '+' : '-'}{movement.quantity}
                          </TableCell>
                          <TableCell>
                            {movement.reference_number ? (
                              <code className="text-sm">{movement.reference_number}</code>
                            ) : (
                              <span className="text-gray-400">—</span>
                            )}
                          </TableCell>
                          <TableCell className="max-w-[200px] truncate">
                            {movement.notes || '—'}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Stock Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <TrendingUp className="h-5 w-5" />
                  Stock Status
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="text-center">
                  <Badge variant={stockStatus.variant} className="mb-2">
                    {stockStatus.label}
                  </Badge>
                </div>
                
                {product.track_stock && product.stock_in_current_workspace ? (
                  <div className="space-y-3">
                    <div className="text-center">
                      <div className="text-3xl font-bold text-gray-900 dark:text-white">
                        {product.stock_in_current_workspace.quantity}
                      </div>
                      <div className="text-sm text-gray-500">Units in stock</div>
                    </div>
                    
                    <Separator />
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Minimum Level:</span>
                      <span className="font-medium">{product.stock_in_current_workspace.minimum_quantity}</span>
                    </div>
                    
                    {product.stock_in_current_workspace.quantity <= product.stock_in_current_workspace.minimum_quantity && (
                      <div className="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-3">
                        <div className="flex items-center gap-2 text-orange-800 dark:text-orange-200">
                          <TrendingUp className="h-4 w-4" />
                          <span className="text-sm font-medium">Stock is running low</span>
                        </div>
                        <p className="text-xs text-orange-600 dark:text-orange-300 mt-1">
                          Consider reordering soon to avoid stockouts.
                        </p>
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-center text-gray-500">
                    {product.track_stock ? 'No stock data available' : 'Stock tracking is disabled'}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Quick Stats */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <DollarSign className="h-5 w-5" />
                  Quick Stats
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Created:</span>
                  <span className="text-sm font-medium">{formatDate(product.created_at)}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Last Updated:</span>
                  <span className="text-sm font-medium">{formatDate(product.updated_at)}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600 dark:text-gray-400">Track Stock:</span>
                  <Badge variant={product.track_stock ? 'default' : 'secondary'}>
                    {product.track_stock ? 'Yes' : 'No'}
                  </Badge>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
