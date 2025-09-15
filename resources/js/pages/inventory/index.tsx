import { Package, TrendingUp, ArrowLeftRight, RotateCcw, Plus, AlertTriangle, Calendar, DollarSign } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventario',
    href: '/inventory',
  },
];

interface Props {
  stats?: {
    totalProducts: number;
    lowStockProducts: number;
    outOfStockProducts: number;
    totalStockValue: number;
    recentMovements: number;
  };
}

export default function InventoryOverview({ stats }: Props) {
  const { workspace } = usePage().props as { workspace?: { current: any } };
  
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Inventario" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Inventario</h1>
            <p className="text-muted-foreground">
              Maneja el inventario y movimientos de {workspace?.current?.name || 'tu espacio de trabajo'}
            </p>
          </div>
        </div>

        {/* Statistics Cards */}
        {stats && (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Productos</CardTitle>
                <Package className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{stats.totalProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Productos con inventario
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Inventario bajo</CardTitle>
                <AlertTriangle className="h-4 w-4 text-orange-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-orange-600">{stats.lowStockProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Debajo del umbral mínimo
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Agotado</CardTitle>
                <Package className="h-4 w-4 text-red-500" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-red-600">{stats.outOfStockProducts}</div>
                <p className="text-xs text-muted-foreground">
                  Artículos sin cantidad
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Valor total</CardTitle>
                <DollarSign className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{formatCurrency(stats.totalStockValue)}</div>
                <p className="text-xs text-muted-foreground">
                  Valor actual del inventario
                </p>
              </CardContent>
            </Card>
          </div>
        )}

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Acciones rápidas</CardTitle>
            <CardDescription>
              Tareas comunes de inventario
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              <Button asChild className="h-auto p-6">
                <Link href="/stock-adjustments">
                  <div className="flex flex-col items-center space-y-3">
                    <RotateCcw className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Ajuste de inventario</div>
                      <div className="text-sm text-muted-foreground">Ajustar cantidades</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/stock-transfers">
                  <div className="flex flex-col items-center space-y-3">
                    <ArrowLeftRight className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Transferencia de inventario</div>
                      <div className="text-sm text-muted-foreground">Mover entre sucursales</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/initial-stock">
                  <div className="flex flex-col items-center space-y-3">
                    <TrendingUp className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Inventario inicial</div>
                      <div className="text-sm text-muted-foreground">Configurar inventario inicial</div>
                    </div>
                  </div>
                </Link>
              </Button>

              <Button asChild className="h-auto p-6">
                <Link href="/products">
                  <div className="flex flex-col items-center space-y-3">
                    <Package className="h-8 w-8" />
                    <div className="text-center">
                      <div className="font-medium">Gestionar productos</div>
                      <div className="text-sm text-muted-foreground">Ver todos los productos</div>
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
                Niveles de inventario
              </CardTitle>
              <CardDescription>
                Monitorear niveles de inventario actuales
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-adjustments">
                  <RotateCcw className="h-4 w-4 mr-2" />
                  Ver inventario actual
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/products?low_stock=true">
                  <AlertTriangle className="h-4 w-4 mr-2" />
                  Productos con inventario bajo
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-adjustments/create">
                  <Plus className="h-4 w-4 mr-2" />
                  Crear ajuste
                </Link>
              </Button>
            </CardContent>
          </Card>

          {/* Movement History Card */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Calendar className="h-5 w-5 mr-2" />
                Actividad reciente
              </CardTitle>
              <CardDescription>
                Seguir movimientos recientes de inventario
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-transfers">
                  <ArrowLeftRight className="h-4 w-4 mr-2" />
                  Ver todas las transferencias
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/stock-transfers/create">
                  <Plus className="h-4 w-4 mr-2" />
                  Crear transferencia
                </Link>
              </Button>
              <Button asChild variant="outline" className="w-full justify-start">
                <Link href="/initial-stock/create">
                  <TrendingUp className="h-4 w-4 mr-2" />
                  Establecer inventario inicial
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
