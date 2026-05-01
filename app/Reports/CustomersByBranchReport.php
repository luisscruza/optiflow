<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class CustomersByBranchReport implements ReportContract
{
    public function name(): string
    {
        return 'Clientes por sucursal';
    }

    public function description(): string
    {
        return 'Consolida las sucursales donde cada cliente tuvo facturas o recetas dentro del rango seleccionado.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        $workspaceOptions = Auth::user()?->workspaces
            ->map(fn (Workspace $workspace) => [
                'value' => (string) $workspace->id,
                'label' => $workspace->name,
            ])
            ->toArray() ?? [];

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
                default: 'all',
                options: $workspaceOptions,
                hidden: false,
            ),
            new ReportFilter(
                name: 'start_date',
                label: 'Fecha de inicio',
                type: 'date',
                default: now()->startOfYear()->format('Y-m-d'),
            ),
            new ReportFilter(
                name: 'end_date',
                label: 'Fecha de fin',
                type: 'date',
                default: now()->endOfYear()->format('Y-m-d'),
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
                key: 'phone_number',
                label: 'Telefono',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'workspace_names',
                label: 'Sucursales',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'report_range',
                label: 'Rango',
                type: 'text',
            ),
            new ReportColumn(
                key: 'invoice_count',
                label: 'Facturas',
                type: 'number',
                sortable: true,
                align: 'right',
            ),
            new ReportColumn(
                key: 'prescription_count',
                label: 'Recetas',
                type: 'number',
                sortable: true,
                align: 'right',
            ),
            new ReportColumn(
                key: 'last_prescription_date',
                label: 'Ultima receta',
                type: 'date',
                sortable: true,
            ),
            new ReportColumn(
                key: 'last_invoice_date',
                label: 'Ultima factura',
                type: 'date',
                sortable: true,
            ),
            new ReportColumn(
                key: 'last_invoice_amount',
                label: 'Ultimo monto facturado',
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
        return $this->baseQuery($filters)->select([
            'contacts.id',
            'contacts.name as customer_name',
            DB::raw("COALESCE(contacts.mobile, contacts.phone_primary, contacts.phone_secondary, '') as phone_number"),
            DB::raw("COALESCE(workspace_activity.workspace_names, '') as workspace_names"),
            DB::raw('COALESCE(invoice_stats.invoice_count, 0) as invoice_count'),
            DB::raw('COALESCE(prescription_stats.prescription_count, 0) as prescription_count'),
            'prescription_stats.last_prescription_date',
            'latest_invoice.last_invoice_date',
            'latest_invoice.last_invoice_amount',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'customer_name' => 'customer_name',
            'phone_number' => 'phone_number',
            'workspace_names' => 'workspace_names',
            'invoice_count' => 'invoice_count',
            'prescription_count' => 'prescription_count',
            'last_prescription_date' => 'last_prescription_date',
            'last_invoice_date' => 'last_invoice_date',
            'last_invoice_amount' => 'last_invoice_amount',
            default => 'customer_name',
        };

        if ($sortBy === null) {
            $sortDirection = 'asc';
        }

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(function ($item) use ($filters): array {
                /** @var Contact&object{
                 *     id: int,
                 *     customer_name: string,
                 *     phone_number: string,
                 *     workspace_names: string,
                 *     invoice_count: int|string,
                 *     prescription_count: int|string,
                 *     last_prescription_date: string|null,
                 *     last_invoice_date: string|null,
                 *     last_invoice_amount: float|int|string|null
                 * } $item */
                $id = $item->id;

                return [
                    'id' => $id,
                    'contact_id' => $id,
                    'customer_name' => $item->customer_name,
                    'phone_number' => $item->phone_number,
                    'workspace_names' => $item->workspace_names,
                    'report_range' => $this->formatRange($filters),
                    'invoice_count' => (int) $item->invoice_count,
                    'prescription_count' => (int) $item->prescription_count,
                    'last_prescription_date' => $this->formatDate($item->last_prescription_date),
                    'last_invoice_date' => $this->formatDate($item->last_invoice_date),
                    'last_invoice_amount' => $item->last_invoice_amount !== null ? (float) $item->last_invoice_amount : null,
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
            ->orderBy('customer_name')
            ->get()
            ->map(function ($item) use ($filters): array {
                /** @var Contact&object{
                 *     id: int,
                 *     customer_name: string,
                 *     phone_number: string,
                 *     workspace_names: string,
                 *     invoice_count: int|string,
                 *     prescription_count: int|string,
                 *     last_prescription_date: string|null,
                 *     last_invoice_date: string|null,
                 *     last_invoice_amount: float|int|string|null
                 * } $item */
                $id = $item->id;

                return [
                    'id' => $id,
                    'contact_id' => $id,
                    'customer_name' => $item->customer_name,
                    'phone_number' => $item->phone_number,
                    'workspace_names' => $item->workspace_names,
                    'report_range' => $this->formatRange($filters),
                    'invoice_count' => (int) $item->invoice_count,
                    'prescription_count' => (int) $item->prescription_count,
                    'last_prescription_date' => $this->formatDate($item->last_prescription_date),
                    'last_invoice_date' => $this->formatDate($item->last_invoice_date),
                    'last_invoice_amount' => $item->last_invoice_amount !== null ? (float) $item->last_invoice_amount : null,
                ];
            })
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        /** @var object{
         *     total_customers: int,
         *     total_invoices: float|int|string,
         *     total_prescriptions: float|int|string
         * }|null $totals */
        $totals = $this->baseQuery($filters)
            ->selectRaw('
                COUNT(*) as total_customers,
                COALESCE(SUM(invoice_stats.invoice_count), 0) as total_invoices,
                COALESCE(SUM(prescription_stats.prescription_count), 0) as total_prescriptions
            ')
            ->first();

        $totalWorkspaces = DB::query()
            ->fromSub($this->customerWorkspaceActivitySubquery($filters), 'customer_workspaces')
            ->selectRaw('COUNT(DISTINCT workspace_id) as total_workspaces')
            ->value('total_workspaces');

        return [
            ['key' => 'total_customers', 'label' => 'Clientes', 'value' => (int) ($totals->total_customers ?? 0), 'type' => 'number'],
            ['key' => 'total_workspaces', 'label' => 'Sucursales', 'value' => (int) ($totalWorkspaces ?? 0), 'type' => 'number'],
            ['key' => 'total_invoices', 'label' => 'Facturas', 'value' => (int) ($totals->total_invoices ?? 0), 'type' => 'number'],
            ['key' => 'total_prescriptions', 'label' => 'Recetas', 'value' => (int) ($totals->total_prescriptions ?? 0), 'type' => 'number'],
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
                    private CustomersByBranchReport $report,
                    private array $filters
                ) {}

                public function collection()
                {
                    $data = $this->report->data($this->filters);
                    $columns = $this->report->columns();

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
            'clientes-por-sucursal-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        return Contact::query()
            ->leftJoinSub($this->invoiceStatsSubquery($filters), 'invoice_stats', function ($join): void {
                $join->on('contacts.id', '=', 'invoice_stats.contact_id');
            })
            ->leftJoinSub($this->latestInvoiceSubquery($filters), 'latest_invoice', function ($join): void {
                $join->on('contacts.id', '=', 'latest_invoice.contact_id');
            })
            ->leftJoinSub($this->prescriptionStatsSubquery($filters), 'prescription_stats', function ($join): void {
                $join->on('contacts.id', '=', 'prescription_stats.patient_id');
            })
            ->leftJoinSub($this->workspaceAggregationSubquery($filters), 'workspace_activity', function ($join): void {
                $join->on('contacts.id', '=', 'workspace_activity.customer_id');
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNotNull('invoice_stats.contact_id')
                    ->orWhereNotNull('prescription_stats.patient_id');
            })
            ->whereIn('contacts.contact_type', [ContactType::Customer->value, 'both'])
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query): Builder => $query->where('contacts.name', 'like', '%'.$filters['search'].'%')
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function invoiceStatsSubquery(array $filters = []): QueryBuilder
    {
        $query = $this->invoiceBaseQuery($filters);

        return $query
            ->select('invoices.contact_id', DB::raw('COUNT(*) as invoice_count'))
            ->groupBy('invoices.contact_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function latestInvoiceSubquery(array $filters = []): QueryBuilder
    {
        $latestInvoiceIds = DB::query()
            ->fromSub($this->latestInvoiceKeysSubquery($filters), 'latest_invoice_keys')
            ->select([
                'latest_invoice_keys.contact_id',
                'latest_invoice_keys.last_invoice_date',
                DB::raw('MAX(invoices.id) as last_invoice_id'),
            ])
            ->join('invoices', function ($join): void {
                $join->on('invoices.contact_id', '=', 'latest_invoice_keys.contact_id')
                    ->on('invoices.issue_date', '=', 'latest_invoice_keys.last_invoice_date');
            })
            ->groupBy('latest_invoice_keys.contact_id', 'latest_invoice_keys.last_invoice_date');

        return DB::query()
            ->fromSub($latestInvoiceIds, 'latest_invoice_ids')
            ->join('invoices', 'invoices.id', '=', 'latest_invoice_ids.last_invoice_id')
            ->select([
                'latest_invoice_ids.contact_id',
                'latest_invoice_ids.last_invoice_date',
                'invoices.total_amount as last_invoice_amount',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function latestInvoiceKeysSubquery(array $filters = []): QueryBuilder
    {
        return $this->invoiceBaseQuery($filters)
            ->select([
                'invoices.contact_id',
                DB::raw('MAX(invoices.issue_date) as last_invoice_date'),
            ])
            ->groupBy('invoices.contact_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function prescriptionStatsSubquery(array $filters = []): QueryBuilder
    {
        return $this->prescriptionBaseQuery($filters)
            ->select([
                'prescriptions.patient_id',
                DB::raw('COUNT(*) as prescription_count'),
                DB::raw('MAX(prescriptions.created_at) as last_prescription_date'),
            ])
            ->groupBy('prescriptions.patient_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function workspaceAggregationSubquery(array $filters = []): QueryBuilder
    {
        return DB::query()
            ->fromSub($this->customerWorkspaceActivitySubquery($filters), 'customer_workspaces')
            ->select([
                'customer_id',
                DB::raw("GROUP_CONCAT(DISTINCT workspace_name ORDER BY workspace_name SEPARATOR ', ') as workspace_names"),
                DB::raw('COUNT(DISTINCT workspace_id) as workspace_count'),
            ])
            ->groupBy('customer_id');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function customerWorkspaceActivitySubquery(array $filters = []): QueryBuilder
    {
        $invoiceWorkspaces = $this->invoiceBaseQuery($filters)
            ->join('workspaces', 'invoices.workspace_id', '=', 'workspaces.id')
            ->select([
                'invoices.contact_id as customer_id',
                'workspaces.id as workspace_id',
                'workspaces.name as workspace_name',
            ]);

        $prescriptionWorkspaces = $this->prescriptionBaseQuery($filters)
            ->join('workspaces', 'prescriptions.workspace_id', '=', 'workspaces.id')
            ->select([
                'prescriptions.patient_id as customer_id',
                'workspaces.id as workspace_id',
                'workspaces.name as workspace_name',
            ]);

        return $invoiceWorkspaces->union($prescriptionWorkspaces);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function invoiceBaseQuery(array $filters = []): QueryBuilder
    {
        $query = DB::table('invoices')
            ->whereIn('invoices.workspace_id', $this->accessibleWorkspaceIds());

        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('invoices.workspace_id', $filters['workspace_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('invoices.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('invoices.issue_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function prescriptionBaseQuery(array $filters = []): QueryBuilder
    {
        $query = DB::table('prescriptions')
            ->whereNull('prescriptions.deleted_at')
            ->whereIn('prescriptions.workspace_id', $this->accessibleWorkspaceIds());

        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('prescriptions.workspace_id', $filters['workspace_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('prescriptions.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('prescriptions.created_at', '<=', $filters['end_date']);
        }

        return $query;
    }

    /**
     * @return array<int>
     */
    private function accessibleWorkspaceIds(): array
    {
        return Auth::user()?->workspaces->pluck('id')->map(fn ($id): int => (int) $id)->all() ?? [];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function formatRange(array $filters): string
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($startDate && $endDate) {
            return Carbon::parse((string) $startDate)->format('d/m/Y').' - '.Carbon::parse((string) $endDate)->format('d/m/Y');
        }

        if ($startDate) {
            return 'Desde '.Carbon::parse((string) $startDate)->format('d/m/Y');
        }

        if ($endDate) {
            return 'Hasta '.Carbon::parse((string) $endDate)->format('d/m/Y');
        }

        return 'Todo el periodo';
    }

    private function formatDate(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
