<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ReportContract;
use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Models\Report;
use App\Reports\GeneralSalesReport;
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

        // Get the report implementation
        $reportImplementation = $this->getReportImplementation($reportType);

        // Get filters from request
        $filters = $request->only(['workspace_id', 'start_date', 'end_date', 'customer_id', 'status', 'search']);

        // Execute the report
        $results = $reportImplementation->execute($filters, $request->integer('per_page', 15));

        return Inertia::render('reports/show', [
            'report' => [
                'id' => $report->id,
                'type' => $report->type->value,
                'name' => $report->name,
                'description' => $report->description,
                'group' => $report->group->value,
                'groupLabel' => $report->group->label(),
            ],
            'filters' => array_map(
                fn($filter) => $filter->toArray(),
                $reportImplementation->filters()
            ),
            'columns' => array_map(
                fn($column) => $column->toArray(),
                $reportImplementation->columns()
            ),
            'summary' => $reportImplementation->summary($filters),
            'data' => $results,
            'appliedFilters' => $filters,
        ]);
    }

    private function getReportImplementation(ReportType $type): ReportContract
    {
        return match ($type) {
            ReportType::GeneralSales => new GeneralSalesReport,
            default => throw new \Exception('Report not implemented'),
        };
    }
}
