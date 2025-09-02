import { ArrowLeftRight, ArrowLeft, Package, Building2, Calendar, User } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type StockMovement, type Workspace } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory',
    href: '/inventory',
  },
  {
    title: 'Stock Transfers',
    href: '/stock-transfers',
  },
  {
    title: 'Transfer Details',
    href: '#',
  },
];

interface Props {
  transfer: StockMovement;
  workspace: Workspace;
}

export default function StockTransfersShow({ transfer, workspace }: Props) {
  const formatQuantity = (quantity: number) => {
    return Number(quantity).toLocaleString(undefined, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    });
  };

  const isIncoming = transfer.to_workspace_id === workspace.id;
  const otherWorkspace = isIncoming ? transfer.from_workspace : transfer.to_workspace;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Transfer Details - ${transfer.product?.name}`} />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center space-x-4">
          <Button variant="outline" size="sm" asChild>
            <Link href="/stock-transfers">
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Transfers
            </Link>
          </Button>
          <div className="flex-1">
            <h1 className="text-3xl font-bold tracking-tight">Transfer Details</h1>
            <p className="text-muted-foreground">
              Stock transfer for {transfer.product?.name}
            </p>
          </div>
        </div>

        {/* Transfer Overview */}
        <div className="grid gap-6 md:grid-cols-2">
          {/* Transfer Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <ArrowLeftRight className="h-5 w-5 mr-2" />
                Transfer Information
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Transfer Type</span>
                <Badge variant={isIncoming ? "default" : "destructive"}>
                  {isIncoming ? "Incoming" : "Outgoing"}
                </Badge>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Product</span>
                <div className="text-right">
                  <div className="font-medium">{transfer.product?.name}</div>
                  <div className="text-sm text-muted-foreground">{transfer.product?.sku}</div>
                </div>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Quantity</span>
                <div className="text-right">
                  <span className={`text-2xl font-bold ${
                    isIncoming ? 'text-green-600' : 'text-red-600'
                  }`}>
                    {isIncoming ? '+' : '-'}{formatQuantity(transfer.quantity)}
                  </span>
                </div>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Reference</span>
                <span className="font-mono text-sm">
                  {transfer.reference_number || '-'}
                </span>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Date</span>
                <div className="flex items-center space-x-1">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm">
                    {new Date(transfer.created_at).toLocaleString()}
                  </span>
                </div>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-muted-foreground">Created By</span>
                <div className="flex items-center space-x-1">
                  <User className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm">
                    {transfer.created_by?.name || 'Unknown'}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Workspace Information */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Building2 className="h-5 w-5 mr-2" />
                Workspace Details
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <div className="text-sm font-medium text-muted-foreground mb-2">From Workspace</div>
                <div className="flex items-center space-x-2 p-3 bg-muted rounded-md">
                  <Building2 className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">{transfer.from_workspace?.name}</span>
                  {transfer.from_workspace_id === workspace.id && (
                    <Badge variant="outline" className="text-xs">Current</Badge>
                  )}
                </div>
              </div>

              <div className="flex items-center justify-center">
                <ArrowLeftRight className="h-6 w-6 text-muted-foreground" />
              </div>

              <div>
                <div className="text-sm font-medium text-muted-foreground mb-2">To Workspace</div>
                <div className="flex items-center space-x-2 p-3 bg-muted rounded-md">
                  <Building2 className="h-4 w-4 text-muted-foreground" />
                  <span className="font-medium">{transfer.to_workspace?.name}</span>
                  {transfer.to_workspace_id === workspace.id && (
                    <Badge variant="outline" className="text-xs">Current</Badge>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Notes */}
        {transfer.notes && (
          <Card>
            <CardHeader>
              <CardTitle>Notes</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                {transfer.notes}
              </p>
            </CardContent>
          </Card>
        )}

        {/* Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Related Actions</CardTitle>
            <CardDescription>
              Manage inventory and create new transfers
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-2">
              <Button asChild variant="outline">
                <Link href={`/stock-adjustments/${transfer.product?.id}`}>
                  <Package className="h-4 w-4 mr-2" />
                  View Stock History
                </Link>
              </Button>
              <Button asChild variant="outline">
                <Link href="/stock-transfers/create">
                  <ArrowLeftRight className="h-4 w-4 mr-2" />
                  New Transfer
                </Link>
              </Button>
              <Button asChild variant="outline">
                <Link href="/stock-adjustments/create">
                  Adjust Stock
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>
        </div>
      </div>
    </AppLayout>
  );
}
