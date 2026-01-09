<?php

declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportContract;
use App\DTOs\ReportColumn;
use App\DTOs\ReportFilter;
use App\Models\Contact;
use App\Models\Prescription;
use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class PrescriptionsByDoctorReport implements ReportContract
{
    public function name(): string
    {
        return 'Recetas por evaluador';
    }

    public function description(): string
    {
        return 'Analiza la actividad de prescripciones por optometrista y el seguimiento de ventas.';
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

        $optometristOptions = Contact::query()
            ->optometrists()
            ->orderBy('name')
            ->get()
            ->map(fn(Contact $optometrist) => [
                'value' => (string) $optometrist->id,
                'label' => $optometrist->name,
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
                placeholder: 'Buscar por nombre del paciente',
            ),
            new ReportFilter(
                name: 'workspace_id',
                label: 'Sucursal',
                type: 'select',
                options: $workspaceOptions,
                hidden: true,
            ),
            new ReportFilter(
                name: 'optometrist_id',
                label: 'Evaluador',
                type: 'select',
                options: $optometristOptions,
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
        ];
    }

    /**
     * @return array<ReportColumn>
     */
    public function columns(): array
    {
        return [
            new ReportColumn(
                key: 'patient_name',
                label: 'Paciente',
                type: 'text',
                sortable: true,
            ),
            new ReportColumn(
                key: 'prescription_data',
                label: 'Prescripción',
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
                label: 'Facturas del período',
                type: 'invoices',
                sortable: false,
            ),
            new ReportColumn(
                key: 'view_prescription',
                label: 'Acciones',
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
        return $this->baseQuery($filters)
            ->with(['patient', 'optometrist', 'workspace'])
            ->select([
                'prescriptions.*',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = [], int $perPage = 15, ?string $sortBy = null, string $sortDirection = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $sortColumn = match ($sortBy) {
            'patient_name' => 'contacts.name',
            'prescription_date' => 'prescriptions.created_at',
            default => 'prescriptions.created_at',
        };

        if ($sortBy === 'patient_name') {
            $query->leftJoin('contacts', 'prescriptions.patient_id', '=', 'contacts.id');
        }

        return $query
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->through(fn(Prescription $prescription) => [
                'id' => $prescription->id,
                'prescription_id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'patient_name' => $prescription->patient?->name ?? 'Sin paciente',
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
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function data(array $filters = []): array
    {
        return $this->query($filters)
            ->orderByDesc('prescriptions.created_at')
            ->get()
            ->map(fn(Prescription $prescription) => [
                'id' => $prescription->id,
                'prescription_id' => $prescription->id,
                'patient_id' => $prescription->patient_id,
                'patient_name' => $prescription->patient?->name ?? 'Sin paciente',
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
        $totals = $this->baseQuery($filters)
            ->selectRaw('
                COUNT(*) as total_prescriptions,
                COUNT(DISTINCT prescriptions.patient_id) as total_patients,
                COUNT(DISTINCT prescriptions.optometrist_id) as total_optometrists
            ')
            ->first();

        return [
            ['key' => 'total_prescriptions', 'label' => 'Total de recetas', 'value' => (int) $totals->total_prescriptions, 'type' => 'number'],
            ['key' => 'total_patients', 'label' => 'Pacientes atendidos', 'value' => (int) $totals->total_patients, 'type' => 'number'],
            ['key' => 'total_optometrists', 'label' => 'Optometristas activos', 'value' => (int) $totals->total_optometrists, 'type' => 'number'],
        ];
    }

    /**
     * Get patient invoices within the period
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function getPatientInvoices(int $patientId, array $filters = []): array
    {
        $query = DB::table('invoices')
            ->where('contact_id', $patientId);

        if (! empty($filters['start_date'])) {
            $query->where('issue_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('issue_date', '<=', $filters['end_date']);
        }

        return $query->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'document_number' => $invoice->document_number,
                'total_amount' => (float) $invoice->total_amount,
                'issue_date' => $invoice->issue_date,
                'status' => $this->getInvoiceStatus($invoice->id, (float) $invoice->total_amount),
            ])
            ->toArray();
    }

    /**
     * Get invoice payment status
     */
    private function getInvoiceStatus(int $invoiceId, float $totalAmount): string
    {
        $paidAmount = DB::table('payments')
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

    /**
     * Base query with all filters applied
     *
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = []): Builder
    {
        $userWorkspaceIds = Auth::user()?->workspaces->pluck('id')->toArray() ?? [];

        $query = Prescription::query();

        // Filter by workspace - if not specified, show all user's workspaces
        if (! empty($filters['workspace_id']) && $filters['workspace_id'] !== 'all') {
            $query->where('prescriptions.workspace_id', $filters['workspace_id']);
        } else {
            $query->whereIn('prescriptions.workspace_id', $userWorkspaceIds);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('prescriptions.created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('prescriptions.created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['optometrist_id']) && $filters['optometrist_id'] !== 'all') {
            $query->where('prescriptions.optometrist_id', $filters['optometrist_id']);
        }

        if (! empty($filters['search'])) {
            $query->whereHas('patient', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
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
                    private PrescriptionsByDoctorReport $report,
                    private array $filters
                ) {}

                public function collection()
                {
                    $data = $this->report->data($this->filters);

                    // Flatten complex data structures for Excel
                    return collect($data)->map(function ($row) {
                        return [
                            'patient_name' => $row['patient_name'],
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
                        'Paciente',
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
            'recetas-por-doctor-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
