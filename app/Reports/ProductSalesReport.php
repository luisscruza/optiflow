<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\InvoiceItem;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ProductSalesReport implements ReportContract
{
    public function name(): string
    {
        return 'Ventas por producto/servicio';
    }

    public function description(): string
    {
        return 'Analiza el rendimiento de tus productos y servicios para optimizar tu inventario.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        $workspaceOptions = Auth::user()->workspaces
            ->map(fn(Workspace $workspace) => [
                'value' => (string) $workspace->id,
                'label' => $workspace->name,
            ])
            ->toArray() ?? [];

        // Default to current month
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        return [
            new ReportFilter(
                name: 'search',
                label: 'Buscar',
                type: 'search',
                placeholder: 'Buscar por nombre o SKU del producto',
            ),
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                options: $workspaceOptions,
                hidden: true,
            ),
            new ReportFilter(
                name: 'start_date',
                label: 'Fecha de inicio',
                type: 'date',
                default: $startOfMonth,
            ),
            new ReportFilter(
                name: 'end_date',
                label: 'Fecha de fin',
                type: 'date',
                default: $endOfMonth,
            ),
            new ReportFilter(
                name: 'customer_id',
                label: 'Cliente',
                type: 'select',
                options: [],
                hidden: true,
            ),
            new ReportFilter(
                name: 'status',
                label: 'Estado',
                type: 'select',
                options: [
                    ['value' => 'paid', 'label' => 'Pagada'],
                    ['value' => 'partially_paid', 'label' => 'Parcialmente pagada'],
                    ['value' => 'pending_payment', 'label' => 'Pendiente de pago'],
                ],
                hidden: true,
            ),
        ];
    }

    /**
     * @return array<ReportColumn>
     */
    public function columns(): array
    {
        return [
            new ReportColumn(
                key: 'product_name',
                label: 'Producto/Servicio',
                type: 'text',
                sortable: true,
                href: '/products/{product_id}',
            ),
            new ReportColumn(
                key: 'sku',
                label: 'SKU',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'quantity',
                label: 'Cantidad vendida',
                type: 'number',
                sortable: true,
                align: 'right',
            ),
            new ReportColumn(
                key: 'subtotal_amount',
                label: 'Antes de impuestos',
                type: 'currency',
                sortable: true,
                align: 'right',
            ),
            new ReportColumn(
                key: 'total_amount',
                label: 'Total',
                type: 'currency',
                sortable: true,
                align: 'right',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return $this->baseQuery($filters)
            ->select([
                'products.id',
                'products.name as product_name',
                'products.sku',
                DB::raw('SUM(invoice_items.quantity) as quantity'),
                DB::raw('SUM(invoice_items.subtotal) as subtotal_amount'),
                DB::raw('SUM(invoice_items.total) as total_amount'),
            ])
            ->groupBy('products.id', 'products.name', 'products.sku');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'product_name' => 'product_name',
            'sku' => 'sku',
            'quantity' => 'quantity',
            'subtotal_amount' => 'subtotal_amount',
            'total_amount' => 'total_amount',
            default => 'total_amount',
        };

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => (float) $item->quantity,
                'subtotal_amount' => (float) $item->subtotal_amount,
                'total_amount' => (float) $item->total_amount,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => (float) $item->quantity,
                'subtotal_amount' => (float) $item->subtotal_amount,
                'total_amount' => (float) $item->total_amount,
            ])
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $totals = $this->baseQuery($filters)
            ->selectRaw('
                COUNT(DISTINCT products.id) as total_products,
                COALESCE(SUM(invoice_items.quantity), 0) as total_quantity,
                COALESCE(SUM(invoice_items.subtotal), 0) as subtotal,
                COALESCE(SUM(invoice_items.tax_amount), 0) as taxes,
                COALESCE(SUM(invoice_items.total), 0) as total
            ')
            ->first();

        return [
            ['key' => 'total_products', 'label' => 'Productos/Servicios', 'value' => (int) $totals->total_products, 'type' => 'number'],
            ['key' => 'total_quantity', 'label' => 'Cantidad vendida', 'value' => (float) $totals->total_quantity, 'type' => 'number'],
            ['key' => 'subtotal', 'label' => 'Antes de impuestos', 'value' => (float) $totals->subtotal, 'type' => 'currency'],
            ['key' => 'total', 'label' => 'Total', 'value' => (float) $totals->total, 'type' => 'currency'],
        ];
    }

    /**
     * Base query with all filters applied
     *
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        $userWorkspaceIds = Auth::user()?->workspaces->pluck('id')->toArray() ?? [];

        $query = InvoiceItem::query()
            ->withoutGlobalScopes()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id');

        // Filter by workspace - if not specified, show all user's workspaces
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('invoices.workspace_id', $filters['workspace_id']);
        } else {
            $query->whereIn('invoices.workspace_id', $userWorkspaceIds);
        }

        if (! empty($filters['start_date'])) {
            $query->where('invoices.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('invoices.issue_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('invoices.contact_id', $filters['customer_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('invoices.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('products.name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('products.sku', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function toExcel(array $filters = []): BinaryFileResponse
    {
        return Excel::download(
            new class($this, $filters) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                public function __construct(
                    private ProductSalesReport $report,
                    private array $filters
                ) {}

                public function collection()
                {
                    $data = $this->report->data($this->filters);
                    $columns = $this->report->columns();

                    // Map data to only include the columns defined in the report
                    return collect($data)->map(function ($row) use ($columns) {
                        $mapped = [];
                        foreach ($columns as $column) {
                            $mapped[$column->key] = $row[$column->key] ?? '';
                        }
                        return $mapped;
                    });
                }

                public function headings(): array
                {
                    return array_map(
                        fn($column) => $column->label,
                        $this->report->columns()
                    );
                }
            },
            'ventas-por-producto-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
