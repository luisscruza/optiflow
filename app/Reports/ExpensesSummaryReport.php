<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Enums\ExpenseStatus;
use App\Enums\Permission;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ExpensesSummaryReport implements ReportContract
{
    public function name(): string
    {
        return 'Gastos generales';
    }

    public function description(): string
    {
        return 'Consulta los gastos registrados por suplidor, sucursal, estado e impuestos.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        $user = Auth::user();

        $workspaceOptions = $this->workspaceOptions();
        $supplierOptions = Contact::query()
            ->suppliers()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Contact $contact): array => [
                'value' => (string) $contact->id,
                'label' => $contact->name,
            ])
            ->toArray();

        return [
            new ReportFilter(
                name: 'search',
                label: 'Buscar',
                type: 'search',
                placeholder: 'Buscar por suplidor o comprobante',
            ),
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                default: $user?->can(Permission::ViewAllLocations) ? null : (string) $user?->current_workspace_id,
                options: $workspaceOptions,
                hidden: ! ($user?->can(Permission::ViewAllLocations) ?? false),
            ),
            new ReportFilter(
                name: 'contact_id',
                label: 'Suplidor',
                type: 'select',
                options: $supplierOptions,
            ),
            new ReportFilter(
                name: 'status',
                label: 'Estado',
                type: 'select',
                options: collect(ExpenseStatus::cases())
                    ->map(fn (ExpenseStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
            ),
            new ReportFilter(
                name: 'is_informal',
                label: 'Tipo de factura',
                type: 'select',
                options: [
                    ['value' => '1', 'label' => 'Informal'],
                    ['value' => '0', 'label' => 'Fiscal'],
                ],
            ),
            new ReportFilter(
                name: 'start_date',
                label: 'Fecha de inicio',
                type: 'date',
                default: now()->startOfMonth()->format('Y-m-d'),
            ),
            new ReportFilter(
                name: 'end_date',
                label: 'Fecha de fin',
                type: 'date',
                default: now()->endOfMonth()->format('Y-m-d'),
            ),
        ];
    }

    /**
     * @return array<ReportColumn>
     */
    public function columns(): array
    {
        return [
            new ReportColumn(key: 'document_number', label: 'Comprobante', type: 'link', sortable: true, href: '/expenses/{id}'),
            new ReportColumn(key: 'supplier_name', label: 'Suplidor', type: 'text', sortable: true),
            new ReportColumn(key: 'workspace_name', label: 'Sucursal', type: 'text', sortable: true),
            new ReportColumn(key: 'status', label: 'Estado', type: 'badge', sortable: true),
            new ReportColumn(key: 'issue_date', label: 'Fecha', type: 'date', sortable: true),
            new ReportColumn(key: 'subtotal_amount', label: 'Subtotal', type: 'currency', sortable: true, align: 'right'),
            new ReportColumn(key: 'total_amount', label: 'Total neto', type: 'currency', sortable: true, align: 'right'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return $this->baseQuery($filters)
            ->with(['contact', 'workspace'])
            ->select([
                'expenses.id',
                'expenses.document_number',
                'expenses.issue_date',
                'expenses.subtotal_amount',
                'expenses.itbis_amount',
                'expenses.isc_amount',
                'expenses.withheld_itbis_amount',
                'expenses.withheld_isr_amount',
                'expenses.total_amount',
                'expenses.status',
                'expenses.contact_id',
                'expenses.workspace_id',
                'expenses.is_informal',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'document_number' => 'expenses.document_number',
            'supplier_name' => 'contacts.name',
            'workspace_name' => 'workspaces.name',
            'subtotal_amount' => 'expenses.subtotal_amount',
            'total_amount' => 'expenses.total_amount',
            'status' => 'expenses.status',
            default => 'expenses.issue_date',
        };

        if ($sortBy === 'supplier_name') {
            $query->leftJoin('contacts', 'expenses.contact_id', '=', 'contacts.id');
        }

        if ($sortBy === 'workspace_name') {
            $query->leftJoin('workspaces', 'expenses.workspace_id', '=', 'workspaces.id');
        }

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(fn (Expense $expense): array => [
                'id' => $expense->id,
                'document_number' => $expense->document_number,
                'supplier_name' => $expense->contact?->name ?? 'Sin suplidor',
                'workspace_name' => $expense->workspace?->name ?? 'Sin sucursal',
                'status' => $expense->status->toBadge(),
                'issue_date' => $expense->issue_date->format('d/m/Y'),
                'subtotal_amount' => $expense->subtotal_amount,
                'total_amount' => $expense->total_amount,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderByDesc('expenses.issue_date')
            ->get()
            ->map(fn (Expense $expense): array => [
                'id' => $expense->id,
                'document_number' => $expense->document_number,
                'supplier_name' => $expense->contact?->name ?? 'Sin suplidor',
                'workspace_name' => $expense->workspace?->name ?? 'Sin sucursal',
                'status' => $expense->status->label(),
                'issue_date' => $expense->issue_date->format('d/m/Y'),
                'subtotal_amount' => $expense->subtotal_amount,
                'itbis_amount' => $expense->itbis_amount,
                'isc_amount' => $expense->isc_amount,
                'withheld_itbis_amount' => $expense->withheld_itbis_amount,
                'withheld_isr_amount' => $expense->withheld_isr_amount,
                'total_amount' => $expense->total_amount,
                'is_informal' => $expense->is_informal ? 'Informal' : 'Fiscal',
            ])
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        /** @var object{
         *     total_expenses: int,
         *     subtotal: float|int|string,
         *     itbis: float|int|string,
         *     isc: float|int|string,
         *     withheld_itbis: float|int|string,
         *     withheld_isr: float|int|string,
         *     total: float|int|string
         * }|null $totals */
        $totals = $this->baseQuery($filters)
            ->selectRaw('
                COUNT(*) as total_expenses,
                COALESCE(SUM(subtotal_amount), 0) as subtotal,
                COALESCE(SUM(itbis_amount), 0) as itbis,
                COALESCE(SUM(isc_amount), 0) as isc,
                COALESCE(SUM(withheld_itbis_amount), 0) as withheld_itbis,
                COALESCE(SUM(withheld_isr_amount), 0) as withheld_isr,
                COALESCE(SUM(total_amount), 0) as total
            ')
            ->first();

        return [
            ['key' => 'total_expenses', 'label' => 'Gastos', 'value' => (int) ($totals->total_expenses ?? 0), 'type' => 'number'],
            ['key' => 'subtotal', 'label' => 'Subtotal', 'value' => (float) ($totals->subtotal ?? 0), 'type' => 'currency'],
            ['key' => 'itbis', 'label' => 'ITBIS', 'value' => (float) ($totals->itbis ?? 0), 'type' => 'currency'],
            ['key' => 'isc', 'label' => 'ISC', 'value' => (float) ($totals->isc ?? 0), 'type' => 'currency'],
            ['key' => 'withheld_itbis', 'label' => 'Retención ITBIS', 'value' => (float) ($totals->withheld_itbis ?? 0), 'type' => 'currency'],
            ['key' => 'withheld_isr', 'label' => 'Retención ISR', 'value' => (float) ($totals->withheld_isr ?? 0), 'type' => 'currency'],
            ['key' => 'total', 'label' => 'Total neto', 'value' => (float) ($totals->total ?? 0), 'type' => 'currency'],
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
                    private ExpensesSummaryReport $report,
                    private array $filters
                ) {}

                public function collection()
                {
                    return collect($this->report->data($this->filters));
                }

                public function headings(): array
                {
                    return [
                        'Comprobante',
                        'Suplidor',
                        'Sucursal',
                        'Estado',
                        'Fecha',
                        'Subtotal',
                        'ITBIS',
                        'ISC',
                        'Retencion ITBIS',
                        'Retencion ISR',
                        'Total neto',
                        'Tipo de factura',
                    ];
                }
            },
            'gastos-generales.xlsx'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        $query = Expense::query();
        $user = Auth::user();

        if ($user?->can(Permission::ViewAllLocations)) {
            $query->withoutWorkspaceScope();
        }

        if (! empty($filters['workspace_id'])) {
            $query->withoutWorkspaceScope()->where('expenses.workspace_id', (int) $filters['workspace_id']);
        }

        if (! empty($filters['contact_id'])) {
            $query->where('expenses.contact_id', (int) $filters['contact_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('expenses.status', $filters['status']);
        }

        if (array_key_exists('is_informal', $filters) && $filters['is_informal'] !== '' && $filters['is_informal'] !== null) {
            $query->where('expenses.is_informal', in_array($filters['is_informal'], ['1', 1, true, 'true'], true));
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('expenses.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('expenses.issue_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('expenses.document_number', 'like', "%{$search}%")
                    ->orWhereHas('contact', fn (Builder $contactQuery) => $contactQuery->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * @return array<int, array{value:string, label:string}>
     */
    private function workspaceOptions(): array
    {
        $user = Auth::user();
        $query = Workspace::query()->select(['id', 'name'])->orderBy('name');

        if (! ($user?->can(Permission::ViewAllLocations) ?? false)) {
            $query->whereIn('id', $user?->workspaces()->select('workspaces.id') ?? []);
        }

        return $query->get()
            ->map(fn (Workspace $workspace): array => [
                'value' => (string) $workspace->id,
                'label' => $workspace->name,
            ])
            ->toArray();
    }
}
