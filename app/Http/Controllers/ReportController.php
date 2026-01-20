<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ReportContract;
use App\Enums\ReportGroup;
use App\Enums\ReportType;
use App\Models\Report;
use App\Reports\CustomerSalesReport;
use App\Reports\GeneralSalesReport;
use App\Reports\PrescriptionsByDoctorReport;
use App\Reports\PrescriptionsSummaryReport;
use App\Reports\ProductSalesReport;
use App\Reports\SalesmanSalesReport;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportController extends Controller
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
            ->map(fn (Report $report): array => [
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

        $filterDefinitions = $reportImplementation->filters();

        $defaultFilters = [];
        foreach ($filterDefinitions as $filter) {
            if ($filter->default !== null) {
                $defaultFilters[$filter->name] = $filter->default;
            }
        }

        $requestFilters = $request->only(['workspace_id', 'start_date', 'end_date', 'customer_id', 'salesman_id', 'optometrist_id', 'status', 'search']);
        $filters = array_merge($defaultFilters, array_filter($requestFilters, fn ($v) => $v !== null && $v !== ''));

        // Get sort parameters
        $sortBy = $request->string('sort_by')->toString() ?: null;
        $sortDirection = $request->string('sort_direction', 'desc')->toString();

        // Execute the report
        $results = $reportImplementation->execute($filters, $request->integer('per_page', 15), $sortBy, $sortDirection);

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
                fn ($filter) => $filter->toArray(),
                $filterDefinitions
            ),
            'columns' => array_map(
                fn ($column) => $column->toArray(),
                $reportImplementation->columns()
            ),
            'summary' => $reportImplementation->summary($filters),
            'data' => $results,
            'appliedFilters' => $filters,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function export(string $type, Request $request): BinaryFileResponse
    {
        $reportType = ReportType::from($type);
        $reportImplementation = $this->getReportImplementation($reportType);

        $filterDefinitions = $reportImplementation->filters();

        $defaultFilters = [];
        foreach ($filterDefinitions as $filter) {
            if ($filter->default !== null) {
                $defaultFilters[$filter->name] = $filter->default;
            }
        }

        $requestFilters = $request->only(['workspace_id', 'start_date', 'end_date', 'customer_id', 'salesman_id', 'optometrist_id', 'status', 'search']);
        $filters = array_merge($defaultFilters, array_filter($requestFilters, fn ($v) => $v !== null && $v !== ''));

        return $reportImplementation->toExcel($filters);
    }

    private function getReportImplementation(ReportType $type): ReportContract
    {
        return match ($type) {
            ReportType::GeneralSales => new GeneralSalesReport,
            ReportType::SalesByProduct => new ProductSalesReport,
            ReportType::SalesByCustomer => new CustomerSalesReport,
            ReportType::SalesBySalesman => new SalesmanSalesReport,
            ReportType::PrescriptionsSummary => new PrescriptionsSummaryReport,
            ReportType::PrescriptionsByDoctor => new PrescriptionsByDoctorReport,
            default => throw new Exception('Report not implemented'),
        };
    }
}
