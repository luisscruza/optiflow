<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Models\DocumentSubtype;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class InvoicesVsExpensesReport implements ReportContract
{
    public function name(): string
    {
        return 'Facturas vs gastos';
    }

    public function description(): string
    {
        return 'Compara el subtotal e ITBIS de facturas y gastos para visualizar el impuesto neto por pagar de forma general y por sucursal.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        $user = Auth::user();

        return [
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                default: 'all',
                options: $this->workspaceOptions(),
                hidden: ! ($user?->can(Permission::ViewAllLocations) ?? false),
            ),
            new ReportFilter(
                name: 'document_subtype_id',
                label: 'Tipos de documento',
                type: 'multiselect',
                default: '',
                options: DocumentSubtype::query()
                    ->withoutGlobalScopes()
                    ->forInvoice()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (DocumentSubtype $documentSubtype): array => [
                        'value' => (string) $documentSubtype->id,
                        'label' => $documentSubtype->name,
                    ])
                    ->toArray(),
                hidden: true,
            ),
            new ReportFilter(
                name: 'is_informal',
                label: 'Tipo de gasto',
                type: 'select',
                default: 'all',
                options: [
                    ['value' => 'all', 'label' => 'Formales e informales'],
                    ['value' => '0', 'label' => 'Solo formales'],
                    ['value' => '1', 'label' => 'Solo informales'],
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
            new ReportColumn(key: 'scope_label', label: 'Ambito', type: 'text', sortable: true),
            new ReportColumn(key: 'invoice_count', label: 'Facturas', type: 'number', sortable: true, align: 'right'),
            new ReportColumn(key: 'expense_count', label: 'Gastos', type: 'number', sortable: true, align: 'right'),
            new ReportColumn(key: 'invoice_subtotal', label: 'Subtotal facturado', type: 'currency', sortable: true, align: 'right'),
            new ReportColumn(key: 'expense_subtotal', label: 'Subtotal gastado', type: 'currency', sortable: true, align: 'right'),
            new ReportColumn(key: 'invoice_itbis', label: 'ITBIS vendido', type: 'currency', sortable: true, align: 'right'),
            new ReportColumn(key: 'expense_itbis', label: 'ITBIS comprado', type: 'currency', sortable: true, align: 'right'),
            new ReportColumn(key: 'net_itbis', label: 'ITBIS neto', type: 'currency', sortable: true, align: 'right'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return $this->invoiceBaseQuery($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $rows = collect($this->data($filters));

        $sortKey = match ($sortBy) {
            'scope_label', 'invoice_count', 'expense_count', 'invoice_subtotal', 'expense_subtotal', 'invoice_itbis', 'expense_itbis', 'net_itbis' => $sortBy,
            default => null,
        };

        if ($sortKey !== null) {
            $generalRows = $rows->filter(fn (array $row): bool => $row['scope_type'] === 'general')->values();
            $detailRows = $rows->filter(fn (array $row): bool => $row['scope_type'] !== 'general');
            $detailRows = $sortDirection === 'asc'
                ? $detailRows->sortBy($sortKey, SORT_NATURAL)
                : $detailRows->sortByDesc($sortKey, SORT_NATURAL);

            $rows = $generalRows->concat($detailRows->values());
        }

        $page = Paginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();

        return new Paginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        $rows = [];
        $selectedWorkspaceId = $this->selectedWorkspaceId($filters);

        if ($selectedWorkspaceId === null) {
            $rows[] = $this->buildRow('general', 'General', null, $filters);
        }

        $workspaceRows = $this->workspaceQuery($filters)
            ->get()
            ->map(fn (Workspace $workspace): array => $this->buildRow('workspace', $workspace->name, $workspace->id, $filters))
            ->all();

        return [...$rows, ...$workspaceRows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function summary(array $filters = []): array
    {
        $row = $this->buildRow('general', 'General', $this->selectedWorkspaceId($filters), $filters);

        return [
            ['key' => 'invoice_subtotal', 'label' => 'Subtotal facturado', 'value' => $row['invoice_subtotal'], 'type' => 'currency'],
            ['key' => 'expense_subtotal', 'label' => 'Subtotal gastado', 'value' => $row['expense_subtotal'], 'type' => 'currency'],
            ['key' => 'invoice_itbis', 'label' => 'ITBIS vendido', 'value' => $row['invoice_itbis'], 'type' => 'currency'],
            ['key' => 'expense_itbis', 'label' => 'ITBIS comprado', 'value' => $row['expense_itbis'], 'type' => 'currency'],
            ['key' => 'net_itbis', 'label' => 'ITBIS neto por pagar', 'value' => $row['net_itbis'], 'type' => 'currency'],
            ['key' => 'invoice_count', 'label' => 'Facturas', 'value' => $row['invoice_count'], 'type' => 'number'],
            ['key' => 'expense_count', 'label' => 'Gastos', 'value' => $row['expense_count'], 'type' => 'number'],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function toExcel(array $filters = []): BinaryFileResponse
    {
        return Excel::download(
            new class($this->data($filters)) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings
            {
                /**
                 * @param  array<int, array<string, mixed>>  $rows
                 */
                public function __construct(private array $rows) {}

                public function collection(): Collection
                {
                    return collect($this->rows)->map(fn (array $row): array => [
                        'Ambito' => $row['scope_label'],
                        'Facturas' => $row['invoice_count'],
                        'Gastos' => $row['expense_count'],
                        'Subtotal facturado' => $row['invoice_subtotal'],
                        'Subtotal gastado' => $row['expense_subtotal'],
                        'ITBIS vendido' => $row['invoice_itbis'],
                        'ITBIS comprado' => $row['expense_itbis'],
                        'ITBIS neto' => $row['net_itbis'],
                    ]);
                }

                public function headings(): array
                {
                    return ['Ambito', 'Facturas', 'Gastos', 'Subtotal facturado', 'Subtotal gastado', 'ITBIS vendido', 'ITBIS comprado', 'ITBIS neto'];
                }
            },
            'facturas-vs-gastos.xlsx'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{invoice_count:int, invoice_subtotal:float, invoice_itbis:float}
     */
    private function invoiceTotals(array $filters, ?int $workspaceId = null): array
    {
        /** @var object{invoice_count:int|string|null, invoice_subtotal:float|int|string|null, invoice_itbis:float|int|string|null}|null $totals */
        $totals = $this->invoiceBaseQuery($filters, $workspaceId)
            ->selectRaw('COUNT(*) as invoice_count')
            ->selectRaw('COALESCE(SUM(invoices.subtotal_amount), 0) as invoice_subtotal')
            ->selectRaw('COALESCE(SUM(invoices.tax_amount), 0) as invoice_itbis')
            ->first();

        return [
            'invoice_count' => (int) ($totals->invoice_count ?? 0),
            'invoice_subtotal' => (float) ($totals->invoice_subtotal ?? 0),
            'invoice_itbis' => (float) ($totals->invoice_itbis ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{expense_count:int, expense_subtotal:float, expense_itbis:float}
     */
    private function expenseTotals(array $filters, ?int $workspaceId = null): array
    {
        /** @var object{expense_count:int|string|null, expense_subtotal:float|int|string|null, expense_itbis:float|int|string|null}|null $totals */
        $totals = $this->expenseBaseQuery($filters, $workspaceId)
            ->selectRaw('COUNT(*) as expense_count')
            ->selectRaw('COALESCE(SUM(expenses.subtotal_amount), 0) as expense_subtotal')
            ->selectRaw('COALESCE(SUM(expenses.itbis_amount), 0) as expense_itbis')
            ->first();

        return [
            'expense_count' => (int) ($totals->expense_count ?? 0),
            'expense_subtotal' => (float) ($totals->expense_subtotal ?? 0),
            'expense_itbis' => (float) ($totals->expense_itbis ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function buildRow(string $scopeType, string $scopeLabel, ?int $workspaceId, array $filters): array
    {
        $invoiceTotals = $this->invoiceTotals($filters, $workspaceId);
        $expenseTotals = $this->expenseTotals($filters, $workspaceId);

        return [
            'scope_type' => $scopeType,
            'scope_label' => $scopeLabel,
            'invoice_count' => $invoiceTotals['invoice_count'],
            'expense_count' => $expenseTotals['expense_count'],
            'invoice_subtotal' => $invoiceTotals['invoice_subtotal'],
            'expense_subtotal' => $expenseTotals['expense_subtotal'],
            'invoice_itbis' => $invoiceTotals['invoice_itbis'],
            'expense_itbis' => $expenseTotals['expense_itbis'],
            'net_itbis' => round($invoiceTotals['invoice_itbis'] - $expenseTotals['expense_itbis'], 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function invoiceBaseQuery(array $filters, ?int $workspaceId = null): Builder
    {
        $query = Invoice::query()->withoutGlobalScopes();

        $workspaceIds = $this->allowedWorkspaceIds($filters, $workspaceId);
        if ($workspaceIds !== []) {
            $query->whereIn('invoices.workspace_id', $workspaceIds);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('invoices.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('invoices.issue_date', '<=', $filters['end_date']);
        }

        $query->whereNotIn('invoices.status', [
            InvoiceStatus::Cancelled->value,
            InvoiceStatus::Deleted->value,
            InvoiceStatus::Draft->value,
        ]);

        $documentSubtypeIds = $this->selectedDocumentSubtypeIds($filters);
        if ($documentSubtypeIds !== []) {
            $query->whereIn('invoices.document_subtype_id', $documentSubtypeIds);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function expenseBaseQuery(array $filters, ?int $workspaceId = null): Builder
    {
        $query = Expense::query();

        if (Auth::user()?->can(Permission::ViewAllLocations)) {
            $query->withoutWorkspaceScope();
        }

        $workspaceIds = $this->allowedWorkspaceIds($filters, $workspaceId);
        if ($workspaceIds !== []) {
            $query->withoutWorkspaceScope()->whereIn('expenses.workspace_id', $workspaceIds);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('expenses.issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('expenses.issue_date', '<=', $filters['end_date']);
        }

        $query->where('expenses.status', '!=', ExpenseStatus::Cancelled->value);

        if (($filters['is_informal'] ?? 'all') !== 'all' && ($filters['is_informal'] ?? '') !== '') {
            $query->where('expenses.is_informal', in_array($filters['is_informal'], ['1', 1, true, 'true'], true));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function workspaceQuery(array $filters): Builder
    {
        $query = Workspace::query()->select(['id', 'name'])->orderBy('name');
        $workspaceIds = $this->allowedWorkspaceIds($filters);

        if ($workspaceIds !== []) {
            $query->whereIn('id', $workspaceIds);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int>
     */
    private function allowedWorkspaceIds(array $filters, ?int $forcedWorkspaceId = null): array
    {
        if ($forcedWorkspaceId !== null) {
            return [$forcedWorkspaceId];
        }

        $selectedWorkspaceId = $this->selectedWorkspaceId($filters);
        if ($selectedWorkspaceId !== null) {
            return [$selectedWorkspaceId];
        }

        return Auth::user()?->workspaces->pluck('id')->map(fn (mixed $id): int => (int) $id)->all() ?? [];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int>
     */
    private function selectedDocumentSubtypeIds(array $filters): array
    {
        $value = (string) ($filters['document_subtype_id'] ?? '');

        if ($value === '' || $value === 'all') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): int => (int) mb_trim($item))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function selectedWorkspaceId(array $filters): ?int
    {
        $value = $filters['workspace_id'] ?? null;

        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        return (int) $value;
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
