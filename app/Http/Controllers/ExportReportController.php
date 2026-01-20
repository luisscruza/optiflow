<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ReportContract;
use App\Enums\ReportType;
use App\Reports\CustomerSalesReport;
use App\Reports\GeneralSalesReport;
use App\Reports\PrescriptionsByDoctorReport;
use App\Reports\PrescriptionsSummaryReport;
use App\Reports\ProductSalesReport;
use App\Reports\SalesmanSalesReport;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportReportController
{
    public function __invoke(string $type, Request $request): BinaryFileResponse
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
