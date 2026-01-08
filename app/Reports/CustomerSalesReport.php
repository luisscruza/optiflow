<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CustomerSalesReport implements ReportContract
{
    public function name(): string
    {
        return 'Ventas por clientes';
    }

    public function description(): string
    {
        return 'Analiza el comportamiento de compra de tus clientes para mejorar tu estrategia comercial.';
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
                placeholder: 'Buscar por nombre del cliente',
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
                key: 'customer_name',
                label: 'Cliente',
                type: 'text',
                sortable: true,
                href: '/contacts/{contact_id}',
            ),
            new ReportColumn(
                key: 'invoice_count',
                label: 'Número de facturas',
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
                'contacts.id',
                'contacts.name as customer_name',
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('SUM(invoices.subtotal_amount) as subtotal_amount'),
                DB::raw('SUM(invoices.total_amount) as total_amount'),
            ])
            ->groupBy('contacts.id', 'contacts.name');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByDesc('total_amount')
            ->paginate($perPage)
            ->through(fn($item) => [
                'id' => $item->id,
                'contact_id' => $item->id,
                'customer_name' => $item->customer_name,
                'invoice_count' => (int) $item->invoice_count,
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
                'contact_id' => $item->id,
                'customer_name' => $item->customer_name,
                'invoice_count' => (int) $item->invoice_count,
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
                COUNT(DISTINCT contacts.id) as total_customers,
                COUNT(invoices.id) as total_invoices,
                COALESCE(SUM(invoices.subtotal_amount), 0) as subtotal,
                COALESCE(SUM(invoices.total_amount), 0) as total
            ')
            ->first();

        return [
            ['key' => 'total_customers', 'label' => 'Total de clientes', 'value' => (int) $totals->total_customers, 'type' => 'number'],
            ['key' => 'total_invoices', 'label' => 'Total de facturas', 'value' => (int) $totals->total_invoices, 'type' => 'number'],
            ['key' => 'subtotal', 'label' => 'Antes de impuestos', 'value' => (float) $totals->subtotal, 'type' => 'currency'],
            ['key' => 'total', 'label' => 'Después de impuestos', 'value' => (float) $totals->total, 'type' => 'currency'],
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

        $query = Invoice::query()
            ->withoutGlobalScopes()
            ->join('contacts', 'invoices.contact_id', '=', 'contacts.id');

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

        if (! empty($filters['search'])) {
            $query->where('contacts.name', 'like', '%' . $filters['search'] . '%');
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
                    private CustomerSalesReport $report,
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
            'ventas-por-cliente-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
