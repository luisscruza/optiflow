<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Models\Invoice;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $reportsByGroup = Report::query()
            ->where('is_active', true)
            ->get()
            ->groupBy('group');

        $groupStats = [];
        foreach (ReportGroup::cases() as $group) {
            $groupStats[] = [
                'value' => $group->value,
                'label' => $group->label(),
                'count' => $reportsByGroup->get($group->value)?->count() ?? 0,
            ];
        }

        // Get recent reports (last accessed or featured)
        $recentReports = Report::query()
            ->where('is_active', true)
            ->limit(4)
            ->get()
            ->map(fn($report) => [
                'id' => $report->id,
                'type' => $report->type->value,
                'name' => $report->name,
                'description' => $report->description,
                'group' => $report->group->value,
                'groupLabel' => $report->group->label(),
            ]);

        return Inertia::render('reports/index', [
            'groupStats' => $groupStats,
            'recentReports' => $recentReports,
        ]);
    }

    public function show(string $type, Request $request): Response
    {
        $reportType = ReportType::from($type);
        $report = Report::where('type', $reportType)->firstOrFail();

        // Get filter parameters from request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = match ($reportType) {
            ReportType::SalesByCustomer => $this->getSalesByCustomer($startDate, $endDate),
            default => [],
        };

        return Inertia::render('reports/show', [
            'report' => [
                'id' => $report->id,
                'type' => $report->type->value,
                'name' => $report->name,
                'description' => $report->description,
                'group' => $report->group->value,
                'groupLabel' => $report->group->label(),
            ],
            'data' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function getSalesByCustomer(?string $startDate, ?string $endDate): array
    {
        $query = Invoice::query()
            ->with('customer')
            ->selectRaw('
                customer_id,
                COUNT(*) as total_invoices,
                SUM(subtotal) as total_sales,
                SUM(total_taxes) as total_taxes,
                SUM(total) as total_amount
            ')
            ->groupBy('customer_id');

        if ($startDate) {
            $query->where('invoice_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('invoice_date', '<=', $endDate);
        }

        $results = $query->get();

        return [
            'customers' => $results->map(fn($item) => [
                'customer_id' => $item->customer_id,
                'customer_name' => $item->customer?->name ?? 'Sin cliente',
                'total_invoices' => (int) $item->total_invoices,
                'total_sales' => (float) $item->total_sales,
                'total_taxes' => (float) $item->total_taxes,
                'total_amount' => (float) $item->total_amount,
            ]),
            'summary' => [
                'total_customers' => $results->count(),
                'total_invoices' => $results->sum('total_invoices'),
                'total_sales' => $results->sum('total_sales'),
                'total_amount' => $results->sum('total_amount'),
            ],
        ];
    }
}
