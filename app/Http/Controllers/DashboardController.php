<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        $currentMonth = Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Get daily sales data for the current month
        $dailySales = $this->getDailySalesData($startOfMonth, $endOfMonth);

        // Get summary statistics
        $summaryStats = $this->getSummaryStatistics($startOfMonth, $endOfMonth);

        // Get accounts receivable and payable data
        $accountsData = $this->getAccountsData();

        return Inertia::render('Dashboard/Index', [
            'dailySales' => $dailySales,
            'summaryStats' => $summaryStats,
            'accountsData' => $accountsData,
            'currentMonth' => $currentMonth->format('F Y'),
        ]);
    }

    private function getDailySalesData(Carbon $startDate, Carbon $endDate): array
    {
        // Get daily sales aggregated by issue_date
        $dailyData = Invoice::query()
            ->select(
                DB::raw('DATE(issue_date) as date'),
                DB::raw('COUNT(*) as invoice_count'),
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(subtotal_amount) as total_subtotal')
            )
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled') // Exclude cancelled invoices
            ->groupBy(DB::raw('DATE(issue_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Create array with all dates in the month, filling gaps with zeros
        $salesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayData = $dailyData->get($dateKey);

            $salesData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'day' => $currentDate->format('j'),
                'day_name' => $currentDate->format('D'),
                'invoice_count' => $dayData ? (int) $dayData->invoice_count : 0,
                'total_sales' => $dayData ? (float) $dayData->total_sales : 0,
                'total_tax' => $dayData ? (float) $dayData->total_tax : 0,
                'total_subtotal' => $dayData ? (float) $dayData->total_subtotal : 0,
            ];

            $currentDate->addDay();
        }

        return $salesData;
    }

    /**
     * @return array<string, array<string, float|int>|array<string, float>>
     */
    private function getSummaryStatistics(Carbon $startDate, Carbon $endDate): array
    {
        // Current month totals
        $currentMonthStats = Invoice::query()
            ->withoutGlobalScopes()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_sales,
                SUM(tax_amount) as total_tax,
                AVG(total_amount) as average_invoice_amount
            ')
            ->first();

        // Count unique products sold in current month
        $uniqueProductsSold = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.issue_date', [$startDate, $endDate])
            ->where('invoices.status', '!=', 'cancelled')
            ->count('invoice_items.product_id');

        // Count unique customers with sales in current month
        $customersWithSales = Invoice::query()
            ->withoutGlobalScopes()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->distinct('contact_id')
            ->count('contact_id');

        // Same month last year for comparison
        $lastYearStartDate = $startDate->copy()->subYear()->startOfMonth();
        $lastYearEndDate = $startDate->copy()->subYear()->endOfMonth();

        $lastYearStats = Invoice::query()
            ->withoutGlobalScopes()
            ->whereBetween('issue_date', [$lastYearStartDate, $lastYearEndDate])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_sales,
                SUM(tax_amount) as total_tax
            ')
            ->first();

        // Count unique products sold in same month last year
        $lastYearUniqueProductsSold = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.issue_date', [$lastYearStartDate, $lastYearEndDate])
            ->where('invoices.status', '!=', 'cancelled')
            ->count('invoice_items.product_id');

        // Count unique customers with sales in same month last year
        $lastYearCustomersWithSales = Invoice::query()
            ->withoutGlobalScopes()
            ->whereBetween('issue_date', [$lastYearStartDate, $lastYearEndDate])
            ->where('status', '!=', 'cancelled')
            ->distinct('contact_id')
            ->count('contact_id');

        // Calculate percentage changes
        $salesChange = $this->calculatePercentageChange(
            $lastYearStats->total_sales ?? 0,
            $currentMonthStats->total_sales ?? 0
        );

        $invoiceCountChange = $this->calculatePercentageChange(
            $lastYearStats->total_invoices ?? 0,
            $currentMonthStats->total_invoices ?? 0
        );

        $productsSoldChange = $this->calculatePercentageChange(
            $lastYearUniqueProductsSold,
            $uniqueProductsSold
        );

        $customersWithSalesChange = $this->calculatePercentageChange(
            $lastYearCustomersWithSales,
            $customersWithSales
        );

        return [
            'current_month' => [
                'total_invoices' => (int) ($currentMonthStats->total_invoices ?? 0),
                'total_sales' => (float) ($currentMonthStats->total_sales ?? 0),
                'total_tax' => (float) ($currentMonthStats->total_tax ?? 0),
                'average_invoice_amount' => (float) ($currentMonthStats->average_invoice_amount ?? 0),
                'unique_products_sold' => $uniqueProductsSold,
                'customers_with_sales' => $customersWithSales,
            ],
            'last_year_same_month' => [
                'total_invoices' => (int) ($lastYearStats->total_invoices ?? 0),
                'total_sales' => (float) ($lastYearStats->total_sales ?? 0),
                'total_tax' => (float) ($lastYearStats->total_tax ?? 0),
                'unique_products_sold' => $lastYearUniqueProductsSold,
                'customers_with_sales' => $lastYearCustomersWithSales,
            ],
            'changes' => [
                'sales_percentage' => $salesChange,
                'invoice_count_percentage' => $invoiceCountChange,
                'products_sold_percentage' => $productsSoldChange,
                'customers_with_sales_percentage' => $customersWithSalesChange,
            ],
        ];
    }

    /**
     * @return array<string, array<string, float|int>>
     */
    private function getAccountsData(): array
    {
        // Accounts receivable (outstanding invoices)
        $accountsReceivable = Invoice::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->selectRaw('
                COUNT(*) as pending_count,
                SUM(total_amount) as total_pending,
                COUNT(CASE WHEN due_date < ? THEN 1 END) as overdue_count,
                SUM(CASE WHEN due_date < ? THEN total_amount ELSE 0 END) as overdue_amount
            ', [Carbon::now(), Carbon::now()])
            ->first();

        // Customer returns/refunds (assuming negative amounts or credit notes)
        $customerReturns = Invoice::query()
            ->withoutGlobalScopes()
            ->where('total_amount', '<', 0)
            ->whereBetween('issue_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->selectRaw('
                COUNT(*) as return_count,
                SUM(ABS(total_amount)) as total_returns
            ')
            ->first();

        return [
            'accounts_receivable' => [
                'pending_count' => (int) ($accountsReceivable->pending_count ?? 0),
                'total_pending' => (float) ($accountsReceivable->total_pending ?? 0),
                'overdue_count' => (int) ($accountsReceivable->overdue_count ?? 0),
                'overdue_amount' => (float) ($accountsReceivable->overdue_amount ?? 0),
            ],
            'customer_returns' => [
                'return_count' => (int) ($customerReturns->return_count ?? 0),
                'total_returns' => (float) ($customerReturns->total_returns ?? 0),
            ],
        ];
    }

    private function calculatePercentageChange(float $previous, float $current): float
    {
        $epsilon = 1e-8; // small tolerance to prevent division by near-zero

        if (abs($previous) < $epsilon) {
            // Define what you consider the correct behavior when "previous" is (near) zero
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
