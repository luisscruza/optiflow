import { ArrowLeftRight, Plus, Search, Eye, Calendar, User, Building2 } from 'lucide-react';
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
import { type BreadcrumbItem, type PaginatedStockMovements, type Workspace } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory',
    href: '#',
  },
  {
    title: 'Stock Transfers',
    href: '/stock-transfers',
  },
];

interface Props {
  transfers: PaginatedStockMovements;
  workspace: Workspace;
}

export default function StockTransfersIndex({ transfers, workspace }: Props) {
  const [search, setSearch] = useState('');

  const handleSearch = (value: string) => {
    setSearch(value);
    router.get('/stock-transfers', 
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

  const getTransferBadge = (type: string, workspaceId: number) => {
    if (type === 'transfer_in') {
      return <Badge variant="default">Incoming</Badge>;
    }
    if (type === 'transfer_out') {
      return <Badge variant="destructive">Outgoing</Badge>;
    }
    return <Badge variant="outline">Transfer</Badge>;
  };

  const getTransferDirection = (movement: any, currentWorkspaceId: number) => {
    if (movement.from_workspace_id === currentWorkspaceId) {
      return {
        direction: 'outgoing',
        otherWorkspace: movement.to_workspace,
        label: 'To',
      };
    } else {
      return {
        direction: 'incoming',
        otherWorkspace: movement.from_workspace,
        label: 'From',
      };
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stock Transfers" />

      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="space-y-8">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Stock Transfers</h1>
            <p className="text-muted-foreground">
              Manage stock transfers between workspaces
            </p>
          </div>

          <Button asChild>
            <Link href="/stock-transfers/create">
              <Plus className="mr-2 h-4 w-4" />
              New Transfer
            </Link>
          </Button>
        </div>

        {/* Transfers */}
        <Card>
          <CardHeader>
            <CardTitle>Transfer History</CardTitle>
            <CardDescription>
              Stock transfers involving {workspace.name}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center space-x-4 mb-6">
              <div className="relative flex-1">
                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search transfers..."
                  value={search}
                  onChange={(e) => handleSearch(e.target.value)}
                  className="pl-8"
                />
              </div>
            </div>

            {/* Transfers Table */}
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>Direction</TableHead>
                    <TableHead>Workspace</TableHead>
                    <TableHead className="text-right">Quantity</TableHead>
                    <TableHead>Reference</TableHead>
                    <TableHead>Created By</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {transfers.data.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center py-8">
                        <div className="flex flex-col items-center space-y-2">
                          <ArrowLeftRight className="h-8 w-8 text-muted-foreground" />
                          <p className="text-muted-foreground">No stock transfers found</p>
                          <Button asChild size="sm" variant="outline">
                            <Link href="/stock-transfers/create">Create your first transfer</Link>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ) : (
                    transfers.data.map((transfer) => {
                      const direction = getTransferDirection(transfer, workspace.id);
                      
                      return (
                        <TableRow key={transfer.id}>
                          <TableCell className="font-medium">
                            <div className="flex items-center space-x-2">
                              <ArrowLeftRight className="h-4 w-4 text-muted-foreground" />
                              <span>{transfer.product?.name}</span>
                            </div>
                          </TableCell>
                          <TableCell>
                            {getTransferBadge(transfer.type, workspace.id)}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center space-x-2">
                              <Building2 className="h-4 w-4 text-muted-foreground" />
                              <div>
                                <p className="font-medium">{direction.otherWorkspace?.name}</p>
                                <p className="text-sm text-muted-foreground">{direction.label}</p>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="text-right font-mono">
                            <span className={`font-semibold ${
                              direction.direction === 'incoming' 
                                ? 'text-green-600' 
                                : 'text-red-600'
                            }`}>
                              {direction.direction === 'incoming' ? '+' : '-'}
                              {formatQuantity(transfer.quantity)}
                            </span>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            {transfer.reference_number || '-'}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center space-x-1">
                              <User className="h-3 w-3" />
                              <span className="text-sm">
                                {transfer.created_by?.name || 'Unknown'}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className="text-muted-foreground">
                            <div className="flex items-center space-x-1">
                              <Calendar className="h-3 w-3" />
                              <span className="text-sm">
                                {new Date(transfer.created_at).toLocaleDateString()}
                              </span>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            <Button asChild size="sm" variant="outline">
                              <Link href={`/stock-transfers/${transfer.id}`}>
                                <Eye className="h-3 w-3 mr-1" />
                                View
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
            {transfers.last_page > 1 && (
              <div className="flex items-center justify-between space-x-2 py-4">
                <div className="text-sm text-muted-foreground">
                  Showing {transfers.from} to {transfers.to} of{' '}
                  {transfers.total} results
                </div>
                <div className="flex space-x-2">
                  {transfers.links.prev && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(transfers.links.prev!)}
                    >
                      Previous
                    </Button>
                  )}
                  {transfers.links.next && (
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => router.get(transfers.links.next!)}
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
