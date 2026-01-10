<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PrescriptionsSummaryReport implements ReportContract
{
    public function name(): string
    {
        return 'Resumen de recetas';
    }

    public function description(): string
    {
        return 'Resumen general de recetas médicas por sucursal.';
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
                    ->map(fn (Workspace $workspace) => [
                        'value' => (string) $workspace->id,
                        'label' => $workspace->name,
                    ])
                    ->toArray(),
                placeholder: 'Todas las sucursales',
            ),
            new ReportFilter(
                name: 'optometrist_id',
                label: 'Optometrista',
                type: 'select',
                default: 'all',
                options: Contact::query()
                    ->optometrists()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Contact $contact) => [
                        'value' => (string) $contact->id,
                        'label' => $contact->name,
                    ])
                    ->toArray(),
                placeholder: 'Todos los optometristas',
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
                label: 'Buscar paciente',
                type: 'search',
                default: '',
                placeholder: 'Buscar por nombre de paciente...',
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
                key: 'workspace_name',
                label: 'Sucursal',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'patient_name',
                label: 'Paciente',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'optometrist_name',
                label: 'Optometrista',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'prescription_data',
                label: 'Datos de receta',
                type: 'prescription',
                sortable: false,
            ),
            new ReportColumn(
                key: 'prescription_date',
                label: 'Fecha',
                type: 'date',
                sortable: true,
            ),
            new ReportColumn(
                key: 'invoices',
                label: 'Facturas',
                type: 'invoices',
                sortable: false,
            ),
            new ReportColumn(
                key: 'view_prescription',
                label: 'Ver receta',
                type: 'link',
                sortable: false,
                align: 'center',
                href: '/prescriptions/{prescription_id}',
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters = []): Builder
    {
        return $this->baseQuery($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'workspace_name' => 'workspaces.name',
            'patient_name' => 'contacts.name',
            'optometrist_name' => 'optometrist.name',
            'prescription_date' => 'prescriptions.created_at',
            default => 'prescriptions.created_at',
        };

        if ($sortBy === 'patient_name') {
            $query->leftJoin('contacts', 'prescriptions.patient_id', '=', 'contacts.id');
        } elseif ($sortBy === 'optometrist_name') {
            $query->leftJoin('contacts as optometrist', 'prescriptions.optometrist_id', '=', 'optometrist.id');
        }

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(function (Prescription $prescription) use ($filters) {
                return [
                    'id' => $prescription->id,
                    'prescription_id' => $prescription->id,
                    'workspace_id' => $prescription->workspace_id,
                    'workspace_name' => $prescription->workspace_name,
                    'patient_id' => $prescription->patient_id,
                    'patient_name' => $prescription->patient?->name ?? 'Sin paciente',
                    'optometrist_name' => $prescription->optometrist?->name ?? 'Sin optometrista',
                    'prescription_data' => [
                        'od' => [
                            'esfera' => $prescription->subjetivo_od_esfera,
                            'cilindro' => $prescription->subjetivo_od_cilindro,
                            'eje' => $prescription->subjetivo_od_eje,
                            'add' => $prescription->subjetivo_od_add,
                            'av_lejos' => $prescription->subjetivo_od_av_lejos,
                            'av_cerca' => $prescription->subjetivo_od_av_cerca,
                        ],
                        'oi' => [
                            'esfera' => $prescription->subjetivo_oi_esfera,
                            'cilindro' => $prescription->subjetivo_oi_cilindro,
                            'eje' => $prescription->subjetivo_oi_eje,
                            'add' => $prescription->subjetivo_oi_add,
                            'av_lejos' => $prescription->subjetivo_oi_av_lejos,
                            'av_cerca' => $prescription->subjetivo_oi_av_cerca,
                        ],
                    ],
                    'prescription_date' => $prescription->created_at->format('d/m/Y'),
                    'invoices' => $this->getPatientInvoices($prescription->patient_id, $filters),
                    'view_prescription' => 'Ver receta',
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderByDesc('prescriptions.created_at')
            ->get()
            ->map(fn (Prescription $prescription) => [
                'id' => $prescription->id,
                'prescription_id' => $prescription->id,
                'workspace_id' => $prescription->workspace_id,
                'workspace_name' => $prescription->workspace_name,
                'patient_id' => $prescription->patient_id,
                'patient_name' => $prescription->patient?->name ?? 'Sin paciente',
                'optometrist_name' => $prescription->optometrist?->name ?? 'Sin optometrista',
                'prescription_data' => [
                    'od' => [
                        'esfera' => $prescription->subjetivo_od_esfera,
                        'cilindro' => $prescription->subjetivo_od_cilindro,
                        'eje' => $prescription->subjetivo_od_eje,
                        'add' => $prescription->subjetivo_od_add,
                        'av_lejos' => $prescription->subjetivo_od_av_lejos,
                        'av_cerca' => $prescription->subjetivo_od_av_cerca,
                    ],
                    'oi' => [
                        'esfera' => $prescription->subjetivo_oi_esfera,
                        'cilindro' => $prescription->subjetivo_oi_cilindro,
                        'eje' => $prescription->subjetivo_oi_eje,
                        'add' => $prescription->subjetivo_oi_add,
                        'av_lejos' => $prescription->subjetivo_oi_av_lejos,
                        'av_cerca' => $prescription->subjetivo_oi_av_cerca,
                    ],
                ],
                'prescription_date' => $prescription->created_at->format('d/m/Y'),
                'invoices' => $this->getPatientInvoices($prescription->patient_id, $filters),
                'view_prescription' => 'Ver receta',
            ])
            ->toArray();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters = []): array
    {
        $query = Prescription::query()
            ->join('workspaces', 'prescriptions.workspace_id', '=', 'workspaces.id');

        // Filter by workspace
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('prescriptions.workspace_id', $filters['workspace_id']);
        }

        // Filter by optometrist
        if (! empty($filters['optometrist_id']) && $filters['optometrist_id'] !== 'all') {
            $query->where('prescriptions.optometrist_id', $filters['optometrist_id']);
        }

        // Filter by date range
        if (! empty($filters['start_date'])) {
            $query->whereDate('prescriptions.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('prescriptions.created_at', '<=', $filters['end_date']);
        }

        // Search by patient name
        if (! empty($filters['search'])) {
            $query->whereHas('patient', function (Builder $q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%');
            });
        }

        $totals = $query
            ->selectRaw('
                COUNT(*) as total_prescriptions,
                COUNT(DISTINCT prescriptions.workspace_id) as total_workspaces,
                COUNT(DISTINCT prescriptions.patient_id) as total_patients,
                COUNT(DISTINCT prescriptions.optometrist_id) as total_optometrists
            ')
            ->first();

        return [
            [
                'label' => 'Total recetas',
                'value' => $totals->total_prescriptions ?? 0,
                'type' => 'number',
            ],
            [
                'label' => 'Sucursales',
                'value' => $totals->total_workspaces ?? 0,
                'type' => 'number',
            ],
            [
                'label' => 'Pacientes',
                'value' => $totals->total_patients ?? 0,
                'type' => 'number',
            ],
            [
                'label' => 'Optometristas',
                'value' => $totals->total_optometrists ?? 0,
                'type' => 'number',
            ],
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
                    private PrescriptionsSummaryReport $report,
                    private array $filters
                ) {}

                public function collection()
                {
                    $data = $this->report->data($this->filters);

                    // Flatten complex data structures for Excel
                    return collect($data)->map(function ($row) {
                        return [
                            'workspace_name' => $row['workspace_name'],
                            'patient_name' => $row['patient_name'],
                            'optometrist_name' => $row['optometrist_name'],
                            'od_esfera' => $row['prescription_data']['od']['esfera'] ?? '',
                            'od_cilindro' => $row['prescription_data']['od']['cilindro'] ?? '',
                            'od_eje' => $row['prescription_data']['od']['eje'] ?? '',
                            'od_add' => $row['prescription_data']['od']['add'] ?? '',
                            'oi_esfera' => $row['prescription_data']['oi']['esfera'] ?? '',
                            'oi_cilindro' => $row['prescription_data']['oi']['cilindro'] ?? '',
                            'oi_eje' => $row['prescription_data']['oi']['eje'] ?? '',
                            'oi_add' => $row['prescription_data']['oi']['add'] ?? '',
                            'prescription_date' => $row['prescription_date'],
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        'Sucursal',
                        'Paciente',
                        'Optometrista',
                        'OD Esfera',
                        'OD Cilindro',
                        'OD Eje',
                        'OD Add',
                        'OI Esfera',
                        'OI Cilindro',
                        'OI Eje',
                        'OI Add',
                        'Fecha',
                    ];
                }
            },
            'resumen-recetas-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        $query = Prescription::query()
            ->with(['patient', 'optometrist'])
            ->join('workspaces', 'prescriptions.workspace_id', '=', 'workspaces.id');

        // Filter by workspace
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('prescriptions.workspace_id', $filters['workspace_id']);
        }

        // Filter by optometrist
        if (! empty($filters['optometrist_id']) && $filters['optometrist_id'] !== 'all') {
            $query->where('prescriptions.optometrist_id', $filters['optometrist_id']);
        }

        // Filter by date range
        if (! empty($filters['start_date'])) {
            $query->whereDate('prescriptions.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('prescriptions.created_at', '<=', $filters['end_date']);
        }

        // Search by patient name
        if (! empty($filters['search'])) {
            $query->whereHas('patient', function (Builder $q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->select('prescriptions.*', 'workspaces.name as workspace_name');
    }

    /**
     * Get invoices for a patient within the filtered date range.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getPatientInvoices(int $patientId, array $filters = []): array
    {
        $query = Invoice::query()
            ->where('contact_id', $patientId);

        // Apply date range filter if present
        if (! empty($filters['start_date'])) {
            $query->whereDate('issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('issue_date', '<=', $filters['end_date']);
        }

        // Apply workspace filter if present
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        return $query
            ->orderByDesc('issue_date')
            ->get()
            ->map(function (Invoice $invoice) {
                return [
                    'id' => $invoice->id,
                    'document_number' => $invoice->document_number,
                    'total_amount' => $invoice->total_amount,
                    'issue_date' => $invoice->issue_date->format('d/m/Y'),
                    'status' => $this->getInvoiceStatus($invoice->id, (float) $invoice->total_amount),
                ];
            })
            ->toArray();
    }

    /**
     * Get the payment status of an invoice.
     */
    private function getInvoiceStatus(int $invoiceId, float $totalAmount): string
    {
        $paidAmount = Payment::query()
            ->where('invoice_id', $invoiceId)
            ->sum('amount');

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partially_paid';
        }

        return 'pending_payment';
    }
}
