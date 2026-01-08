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
use Illuminate\Support\Facades\Auth;

final readonly class GeneralSalesReport implements ReportContract
{
    public function name(): string
    {
        return 'Ventas generales';
    }

    public function description(): string
    {
        return 'Revisa el desempeño de tus ventas para crear estrategias comerciales.';
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
                placeholder: 'Buscar por cliente o número de documento',
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
                key: 'document_number',
                label: 'Número de documento',
                type: 'link',
                sortable: true,
                href: '/invoices/{id}',
            ),
            new ReportColumn(
                key: 'customer_name',
                label: 'Cliente',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'status',
                label: 'Estado',
                type: 'badge',
                sortable: true,
            ),
            new ReportColumn(
                key: 'issue_date',
                label: 'Fecha de creación',
                type: 'date',
                sortable: true,
            ),
            new ReportColumn(
                key: 'subtotal_amount',
                label: 'Antes de impuestos',
                type: 'currency',
                align: 'right',
            ),
            new ReportColumn(
                key: 'total_amount',
                label: 'Total de la factura',
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
            ->with(['contact', 'payments'])
            ->select([
                'invoices.id',
                'invoices.document_number',
                'invoices.issue_date',
                'invoices.subtotal_amount',
                'invoices.total_amount',
                'invoices.contact_id',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderBy('issue_date', 'desc')
            ->paginate($perPage)
            ->through(fn(Invoice $invoice) => [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                'customer_name' => $invoice->contact?->name ?? 'Sin cliente',
                'status' => $invoice->status_config,
                'issue_date' => $invoice->issue_date->format('d/m/Y'),
                'subtotal_amount' => $invoice->subtotal_amount,
                'total_amount' => $invoice->total_amount,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(fn(Invoice $invoice) => [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                'customer_name' => $invoice->contact?->name ?? 'Sin cliente',
                'status' => $invoice->status->label(),
                'issue_date' => $invoice->issue_date->format('d/m/Y'),
                'total_amount' => $invoice->total_amount,
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
                COUNT(*) as total_invoices,
                COALESCE(SUM(subtotal_amount), 0) as subtotal,
                COALESCE(SUM(tax_amount), 0) as taxes,
                COALESCE(SUM(total_amount), 0) as total
            ')
            ->first();

        return [
            ['key' => 'total_invoices', 'label' => 'Facturas', 'value' => (int) $totals->total_invoices, 'type' => 'number'],
            ['key' => 'subtotal', 'label' => 'Antes de impuestos', 'value' => (float) $totals->subtotal, 'type' => 'currency'],
            ['key' => 'taxes', 'label' => 'Impuestos', 'value' => (float) $totals->taxes, 'type' => 'currency'],
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

        $query = Invoice::query()->withoutGlobalScopes();

        // Filter by workspace - if not specified, show all user's workspaces
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('workspace_id', $filters['workspace_id']);
        } else {
            $query->whereIn('workspace_id', $userWorkspaceIds);
        }

        if (! empty($filters['start_date'])) {
            $query->where('issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('issue_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('contact_id', $filters['customer_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('document_number', 'like', '%' . $filters['search'] . '%')
                    ->orWhereHas('contact', function ($q) use ($filters) {
                        $q->where('name', 'like', '%' . $filters['search'] . '%');
                    });
            });
        }

        return $query;
    }
}
