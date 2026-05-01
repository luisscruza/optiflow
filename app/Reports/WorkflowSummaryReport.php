<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\WorkflowJob;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class WorkflowSummaryReport implements ReportContract
{
    public function name(): string
    {
        return 'Procesos por sucursal';
    }

    public function description(): string
    {
        return 'Consulta los procesos creados por sucursal y mes, mostrando su relacion con facturas o recetas dentro del rango seleccionado.';
    }

    /**
     * @return array<ReportFilter>
     */
    public function filters(): array
    {
        return [
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                default: 'all',
                options: Workspace::query()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Workspace $workspace): array => [
                        'value' => (string) $workspace->id,
                        'label' => $workspace->name,
                    ])
                    ->toArray(),
                placeholder: 'Todas las sucursales',
            ),
            new ReportFilter(
                name: 'start_date',
                label: 'Fecha inicial',
                type: 'date',
                default: now()->startOfMonth()->format('Y-m-d'),
            ),
            new ReportFilter(
                name: 'end_date',
                label: 'Fecha final',
                type: 'date',
                default: now()->endOfMonth()->format('Y-m-d'),
            ),
            new ReportFilter(
                name: 'search',
                label: 'Buscar',
                type: 'search',
                default: '',
                placeholder: 'Buscar por proceso, workflow, cliente o relacion...',
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
                key: 'month_label',
                label: 'Mes',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'process_date',
                label: 'Fecha',
                type: 'date',
                sortable: true,
            ),
            new ReportColumn(
                key: 'workspace_name',
                label: 'Sucursal',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'workflow_name',
                label: 'Workflow',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'process_label',
                label: 'Proceso',
                type: 'link',
                sortable: true,
                href: '/workflows/{workflow_id}/jobs/{id}',
            ),
            new ReportColumn(
                key: 'contact_name',
                label: 'Cliente',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'relation_type',
                label: 'Relacion',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'invoice_label',
                label: 'Factura',
                type: 'link',
                href: '/invoices/{invoice_id}',
            ),
            new ReportColumn(
                key: 'prescription_label',
                label: 'Receta',
                type: 'link',
                href: '/prescriptions/{prescription_id}',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return $this->baseQuery($filters)->select([
            'workflow_jobs.*',
            'workspaces.name as workspace_name',
            'workflows.name as workflow_name',
            'contacts.name as contact_name',
            'invoices.document_number as invoice_document_number',
            'prescriptions.id as related_prescription_id',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'month_label', 'process_date' => 'workflow_jobs.created_at',
            'workspace_name' => 'workspaces.name',
            'workflow_name' => 'workflows.name',
            'process_label' => 'workflow_jobs.id',
            'contact_name' => 'contacts.name',
            'relation_type' => 'workflow_jobs.invoice_id',
            default => 'workflow_jobs.created_at',
        };

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(fn (WorkflowJob $job): array => $this->transformRow($job));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderByDesc('workflow_jobs.created_at')
            ->get()
            ->map(fn (WorkflowJob $job): array => $this->transformRow($job))
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function summary(array $filters = []): array
    {
        /** @var object{total_jobs:int,total_workspaces:int,total_invoice_relations:int,total_prescription_relations:int}|null $totals */
        $totals = $this->baseQuery($filters)
            ->selectRaw('COUNT(*) as total_jobs')
            ->selectRaw('COUNT(DISTINCT workflow_jobs.workspace_id) as total_workspaces')
            ->selectRaw('SUM(CASE WHEN workflow_jobs.invoice_id IS NOT NULL THEN 1 ELSE 0 END) as total_invoice_relations')
            ->selectRaw('SUM(CASE WHEN workflow_jobs.prescription_id IS NOT NULL THEN 1 ELSE 0 END) as total_prescription_relations')
            ->first();

        return [
            ['key' => 'total_jobs', 'label' => 'Procesos', 'value' => (int) ($totals->total_jobs ?? 0), 'type' => 'number'],
            ['key' => 'total_workspaces', 'label' => 'Sucursales', 'value' => (int) ($totals->total_workspaces ?? 0), 'type' => 'number'],
            ['key' => 'total_invoice_relations', 'label' => 'Con factura', 'value' => (int) ($totals->total_invoice_relations ?? 0), 'type' => 'number'],
            ['key' => 'total_prescription_relations', 'label' => 'Con receta', 'value' => (int) ($totals->total_prescription_relations ?? 0), 'type' => 'number'],
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
                        'Mes' => $row['month_label'],
                        'Fecha' => $row['process_date'],
                        'Sucursal' => $row['workspace_name'],
                        'Workflow' => $row['workflow_name'],
                        'Proceso' => $row['process_label'],
                        'Cliente' => $row['contact_name'],
                        'Relacion' => $row['relation_type'],
                        'Factura' => $row['invoice_label'],
                        'Receta' => $row['prescription_label'],
                    ]);
                }

                public function headings(): array
                {
                    return ['Mes', 'Fecha', 'Sucursal', 'Workflow', 'Proceso', 'Cliente', 'Relacion', 'Factura', 'Receta'];
                }
            },
            'procesos-por-sucursal.xlsx'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters): Builder
    {
        $query = WorkflowJob::query()
            ->withoutGlobalScopes()
            ->leftJoin('workspaces', 'workflow_jobs.workspace_id', '=', 'workspaces.id')
            ->leftJoin('workflows', 'workflow_jobs.workflow_id', '=', 'workflows.id')
            ->leftJoin('contacts', 'workflow_jobs.contact_id', '=', 'contacts.id')
            ->leftJoin('invoices', 'workflow_jobs.invoice_id', '=', 'invoices.id')
            ->leftJoin('prescriptions', 'workflow_jobs.prescription_id', '=', 'prescriptions.id');

        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('workflow_jobs.workspace_id', $filters['workspace_id']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('workflow_jobs.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('workflow_jobs.created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['search'])) {
            $search = mb_trim((string) $filters['search']);

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('workflow_jobs.id', 'like', "%{$search}%")
                    ->orWhere('workflows.name', 'like', "%{$search}%")
                    ->orWhere('contacts.name', 'like', "%{$search}%")
                    ->orWhere('invoices.document_number', 'like', "%{$search}%")
                    ->orWhere('prescriptions.id', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function transformRow(WorkflowJob $job): array
    {
        /** @var WorkflowJob&object{workspace_name:string|null,workflow_name:string|null,contact_name:string|null,invoice_document_number:string|null,related_prescription_id:int|string|null} $job */
        $createdAt = $job->created_at instanceof Carbon ? $job->created_at : Carbon::parse($job->created_at);
        $prescriptionId = $job->related_prescription_id !== null ? (int) $job->related_prescription_id : null;

        return [
            'id' => $job->id,
            'workflow_id' => $job->workflow_id,
            'invoice_id' => $job->invoice_id,
            'prescription_id' => $prescriptionId,
            'month_label' => $createdAt->locale('es')->translatedFormat('F Y'),
            'process_date' => $createdAt->format('d/m/Y'),
            'workspace_name' => $job->workspace_name ?? 'Sin sucursal',
            'workflow_name' => $job->workflow_name ?? 'Sin workflow',
            'process_label' => Str::upper(Str::substr($job->id, 0, 8)),
            'contact_name' => $job->contact_name ?? 'Sin cliente',
            'relation_type' => $this->relationTypeLabel($job->invoice_id, $prescriptionId),
            'invoice_label' => $job->invoice_document_number,
            'prescription_label' => $prescriptionId !== null ? "Receta #{$prescriptionId}" : null,
        ];
    }

    private function relationTypeLabel(?int $invoiceId, ?int $prescriptionId): string
    {
        if ($invoiceId !== null && $prescriptionId !== null) {
            return 'Factura y receta';
        }

        if ($invoiceId !== null) {
            return 'Factura';
        }

        if ($prescriptionId !== null) {
            return 'Receta';
        }

        return 'Sin relacion';
    }
}
