<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\Invoice;
use App\Models\Salesman;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class SalesmanSalesReport implements ReportContract
{
    public function name(): string
    {
        return 'Ventas por vendedor';
    }

    public function description(): string
    {
        return 'Evalúa el desempeño de tu equipo de ventas y reconoce a los mejores vendedores.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        $workspaceOptions = Auth::user()->workspaces
            ->map(fn (Workspace $workspace) => [
                'value' => (string) $workspace->id,
                'label' => $workspace->name,
            ])
            ->toArray() ?? [];

        $salesmanOptions = Salesman::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Salesman $salesman) => [
                'value' => (string) $salesman->id,
                'label' => $salesman->full_name,
            ])
            ->toArray();

        // Default to current month
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = now()->endOfMonth()->format('Y-m-d');

        return [
            new ReportFilter(
                name: 'search',
                label: 'Buscar',
                type: 'search',
                placeholder: 'Buscar por nombre del vendedor',
            ),
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                options: $workspaceOptions,
                hidden: true,
            ),
            new ReportFilter(
                name: 'salesman_id',
                label: 'Vendedor',
                type: 'select',
                options: $salesmanOptions,
                hidden: false,
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
                key: 'salesman_name',
                label: 'Vendedor',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'invoices',
                label: 'Número de facturas',
                type: 'invoice_list',
                sortable: false,
                align: 'center',
            ),
            new ReportColumn(
                key: 'paid_amount',
                label: 'Pagado',
                type: 'currency',
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
                label: 'Después de impuestos',
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
                'salesmen.id',
                DB::raw("CONCAT(salesmen.name, ' ', salesmen.surname) as salesman_name"),
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('COALESCE(SUM(payments.amount), 0) as paid_amount'),
                DB::raw('SUM(invoices.subtotal_amount) as subtotal_amount'),
                DB::raw('SUM(invoices.total_amount) as total_amount'),
            ])
            ->groupBy('salesmen.id', 'salesmen.name', 'salesmen.surname');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        // Apply sorting
        $sortColumn = match ($sortBy) {
            'salesman_name' => 'salesman_name',
            'paid_amount' => 'paid_amount',
            'subtotal_amount' => 'subtotal_amount',
            'total_amount' => 'total_amount',
            default => 'total_amount',
        };

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(function ($item) use ($filters) {
                return [
                    'id' => $item->id,
                    'salesman_id' => $item->id,
                    'salesman_name' => $item->salesman_name,
                    'invoices' => $this->getSalesmanInvoices($item->id, $filters),
                    'paid_amount' => (float) $item->paid_amount,
                    'subtotal_amount' => (float) $item->subtotal_amount,
                    'total_amount' => (float) $item->total_amount,
                ];
            });
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
            ->map(fn ($item) => [
                'id' => $item->id,
                'salesman_id' => $item->id,
                'salesman_name' => $item->salesman_name,
                'invoice_count' => (int) $item->invoice_count,
                'paid_amount' => (float) $item->paid_amount,
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
                COUNT(DISTINCT salesmen.id) as total_salesmen,
                COUNT(invoices.id) as total_invoices,
                COALESCE(SUM(payments.amount), 0) as paid,
                COALESCE(SUM(invoices.subtotal_amount), 0) as subtotal,
                COALESCE(SUM(invoices.total_amount), 0) as total
            ')
            ->first();

        return [
            ['key' => 'total_salesmen', 'label' => 'Total de vendedores', 'value' => (int) $totals->total_salesmen, 'type' => 'number'],
            ['key' => 'total_invoices', 'label' => 'Total de facturas', 'value' => (int) $totals->total_invoices, 'type' => 'number'],
            ['key' => 'paid', 'label' => 'Pagado', 'value' => (float) $totals->paid, 'type' => 'currency'],
            ['key' => 'total', 'label' => 'Total', 'value' => (float) $totals->total, 'type' => 'currency'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function toExcel(array $filters = []): BinaryFileResponse
    {
        return Excel::download(
            new class($this, $filters) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
            {
                public function __construct(
                    private SalesmanSalesReport $report,
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
                        fn ($column) => $column->label,
                        $this->report->columns()
                    );
                }
            },
            'ventas-por-vendedor-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * Base query with all filters applied
     *
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        $userWorkspaceIds = Auth::user()?->workspaces->pluck('id')->toArray() ?? [];

        $query = Invoice::query()
            ->withoutGlobalScopes()
            ->join('invoice_salesman', 'invoices.id', '=', 'invoice_salesman.invoice_id')
            ->join('salesmen', 'invoice_salesman.salesman_id', '=', 'salesmen.id')
            ->leftJoin('payments', 'invoices.id', '=', 'payments.invoice_id');

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

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('invoices.status', $filters['status']);
        }

        if (! empty($filters['salesman_id']) && $filters['salesman_id'] !== 'all') {
            $query->where('salesmen.id', $filters['salesman_id']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('salesmen.name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('salesmen.surname', 'like', '%'.$filters['search'].'%')
                    ->orWhereRaw("CONCAT(salesmen.name, ' ', salesmen.surname) like ?", ['%'.$filters['search'].'%']);
            });
        }

        return $query;
    }

    /**
     * Get invoices for a salesman within the filtered date range.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getSalesmanInvoices(int $salesmanId, array $filters = []): array
    {
        $userWorkspaceIds = Auth::user()?->workspaces->pluck('id')->toArray() ?? [];

        $query = Invoice::query()
            ->join('invoice_salesman', 'invoices.id', '=', 'invoice_salesman.invoice_id')
            ->join('workspaces', 'invoices.workspace_id', '=', 'workspaces.id')
            ->where('invoice_salesman.salesman_id', $salesmanId);

        // Filter by workspace
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('invoices.workspace_id', $filters['workspace_id']);
        } else {
            $query->whereIn('invoices.workspace_id', $userWorkspaceIds);
        }

        // Apply date range filter
        if (! empty($filters['start_date'])) {
            $query->where('invoices.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('invoices.issue_date', '<=', $filters['end_date']);
        }

        // Apply status filter
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('invoices.status', $filters['status']);
        }

        return $query
            ->select('invoices.id', 'invoices.document_number', 'invoices.total_amount', 'invoices.issue_date', 'workspaces.name as workspace_name')
            ->orderByDesc('invoices.issue_date')
            ->get()
            ->map(function (Invoice $invoice) {
                return [
                    'id' => $invoice->id,
                    'document_number' => $invoice->document_number,
                    'total_amount' => $invoice->total_amount,
                    'issue_date' => $invoice->issue_date->format('d/m/Y'),
                    'workspace_name' => $invoice->workspace_name,
                ];
            })
            ->toArray();
    }
}
