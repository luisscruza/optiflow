import { Package, TrendingUp, ArrowLeftRight, RotateCcw, Plus, AlertTriangle, Calendar, DollarSign } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Workspace } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory',
    href: '/inventory',
  },
];

interface Props {
  workspace: Workspace;
  stats?: {
    totalProducts: number;
    lowStockProducts: number;
    outOfStockProducts: number;
    totalStockValue: number;
    recentMovements: number;
  };
}

export default function InventoryOverview({ workspace, stats }: Props) {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Inventory Management" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Inventory Management</h1>
            <p className="text-muted-foreground">
              Manage stock levels and movements for {workspace.name}
            </p>
          </div>
        </div>

        {/* Statistics Cards */}
        {stats && (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Products</CardTitle>
                <Package className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.totalProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Stock tracked products
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Low Stock</CardTitle>
                <AlertTriangle className="h-4 w-4 text-orange-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-orange-600">{stats.lowStockProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Below minimum threshold
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Out of Stock</CardTitle>
                <Package className="h-4 w-4 text-red-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-red-600">{stats.outOfStockProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Zero quantity items
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Value</CardTitle>
                <DollarSign className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{formatCurrency(stats.totalStockValue)}</div>
                <p className="text-xs text-muted-foreground">
                  Current stock value
                </p>
              </CardContent>
            </Card>
          </div>
        )}

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>
              Common inventory management tasks
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <Button asChild className="h-auto p-6">
                <Link href="/stock-adjustments">
                  <div className="flex flex-col items-center space-y-3">
                    <RotateCcw className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Stock Adjustments</div>
                      <div className="text-sm text-muted-foreground">Adjust quantities</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/stock-transfers">
                  <div className="flex flex-col items-center space-y-3">
                    <ArrowLeftRight className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Stock Transfers</div>
                      <div className="text-sm text-muted-foreground">Move between workspaces</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/initial-stock">
                  <div className="flex flex-col items-center space-y-3">
                    <TrendingUp className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Initial Stock</div>
                      <div className="text-sm text-muted-foreground">Set up new products</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/products">
                  <div className="flex flex-col items-center space-y-3">
                    <Package className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Manage Products</div>
                      <div className="text-sm text-muted-foreground">View all products</div>
                    </div>
                  </div>
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Quick Links Grid */}
        <div className="grid gap-6 md:grid-cols-2">
          {/* Stock Levels Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Package className="h-5 w-5 mr-2" />
                Stock Levels
              </CardTitle>
              <CardDescription>
                Monitor current inventory levels
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-adjustments">
                  <RotateCcw className="h-4 w-4 mr-2" />
                  View Current Stock
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/products?low_stock=true">
                  <AlertTriangle className="h-4 w-4 mr-2" />
                  Low Stock Products
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-adjustments/create">
                  <Plus className="h-4 w-4 mr-2" />
                  Create Adjustment
                </Link>
              </Button>
            </CardContent>
          </Card>

          {/* Movement History Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Calendar className="h-5 w-5 mr-2" />
                Recent Activity
              </CardTitle>
              <CardDescription>
                Track recent stock movements
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-transfers">
                  <ArrowLeftRight className="h-4 w-4 mr-2" />
                  View All Transfers
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-transfers/create">
                  <Plus className="h-4 w-4 mr-2" />
                  Create Transfer
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/initial-stock/create">
                  <TrendingUp className="h-4 w-4 mr-2" />
                  Set Initial Stock
                </Link>
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
      </div>
    </AppLayout>
  );
}
