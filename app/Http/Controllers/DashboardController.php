<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DashboardWidget;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Workflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    private const RANGE_CURRENT_MONTH = 'current_month';

    private const RANGE_LAST_3_MONTHS = 'last_3_months';

    private const RANGE_LAST_6_MONTHS = 'last_6_months';

    public function index(Request $request): Response
    {
        $range = $request->input('range', self::RANGE_CURRENT_MONTH);

        [$startDate, $endDate] = $this->getDateRangeFromPreset($range);

        // Calculate previous period for comparison
        $periodDays = $startDate->diffInDays($endDate);
        $previousStartDate = $startDate->copy()->subDays($periodDays + 1);
        $previousEndDate = $startDate->copy()->subDay();

        return Inertia::render('Dashboard/Index', [
            'filters' => [
                'range' => $range,
            ],
            'accountsReceivable' => $this->getAccountsReceivable($startDate, $endDate),
            'salesTax' => $this->getSalesTax($startDate, $endDate, $previousStartDate, $previousEndDate),
            'productsSold' => $this->getProductsSold($startDate, $endDate, $previousStartDate, $previousEndDate),
            'customersWithSales' => $this->getCustomersWithSales($startDate, $endDate, $previousStartDate, $previousEndDate),
            'prescriptionsCreated' => $this->getPrescriptionsCreated($startDate, $endDate, $previousStartDate, $previousEndDate),
            'workflowsSummary' => $this->getWorkflowsSummary(),
            'dashboardLayout' => $this->filterLayoutByPermissions(Auth::user()?->dashboard_layout ?? DashboardWidget::defaultLayouts()),
            'availableWidgets' => $this->getAvailableWidgets(),
        ]);
    }

    /**
     * Get available widgets filtered by user permissions.
     *
     * @return array<string, string>
     */
    private function getAvailableWidgets(): array
    {
        $user = Auth::user();
        $widgets = [];

        foreach (DashboardWidget::cases() as $widget) {
            if ($user?->can($widget->requiredPermission()->value)) {
                $widgets[$widget->value] = $widget->label();
            }
        }

        return $widgets;
    }

    /**
     * Filter layout to only include widgets the user has permission to view.
     *
     * @param  array<array{id: string, x: int, y: int, w: int, h: int, minW?: int, minH?: int}>  $layout
     * @return array<array{id: string, x: int, y: int, w: int, h: int, minW?: int, minH?: int}>
     */
    private function filterLayoutByPermissions(array $layout): array
    {
        $user = Auth::user();

        return array_values(array_filter($layout, function (array $widget) use ($user) {
            $dashboardWidget = DashboardWidget::tryFrom($widget['id']);
            if (! $dashboardWidget) {
                return false;
            }

            return $user?->can($dashboardWidget->requiredPermission()->value);
        }));
    }

    /**
     * Get start and end dates from preset range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function getDateRangeFromPreset(string $range): array
    {
        $today = Carbon::today();

        return match ($range) {
            self::RANGE_LAST_3_MONTHS => [
                $today->copy()->subMonths(3)->startOfMonth(),
                $today->copy()->endOfDay(),
            ],
            self::RANGE_LAST_6_MONTHS => [
                $today->copy()->subMonths(6)->startOfMonth(),
                $today->copy()->endOfDay(),
            ],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * Get accounts receivable data filtered by invoice creation date.
     *
     * @return array<string, array<string, float|int>>
     */
    private function getAccountsReceivable(Carbon $startDate, Carbon $endDate): array
    {
        $today = Carbon::today();

        // Get invoices created within the date range that are not fully paid or cancelled
        // We need to calculate amount_due = total_amount - sum(payments)
        $invoices = Invoice::query()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->select([
                'invoices.id',
                'invoices.total_amount',
                'invoices.due_date',
            ])
            ->get();

        // Get all payments for these invoices
        $invoiceIds = $invoices->pluck('id')->toArray();
        $paymentsSum = Payment::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->groupBy('invoice_id')
            ->select('invoice_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'invoice_id');

        $currentAmount = 0.0;
        $currentCount = 0;
        $overdueAmount = 0.0;
        $overdueCount = 0;

        foreach ($invoices as $invoice) {
            $totalPaid = (float) ($paymentsSum[$invoice->id] ?? 0);
            $amountDue = max(0, (float) $invoice->total_amount - $totalPaid);

            // Skip fully paid invoices
            if ($amountDue <= 0) {
                continue;
            }

            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;

            if ($dueDate && $dueDate->lt($today)) {
                // Overdue: due_date < today
                $overdueAmount += $amountDue;
                $overdueCount++;
            } else {
                // Current: due_date >= today (or no due date)
                $currentAmount += $amountDue;
                $currentCount++;
            }
        }

        return [
            'current' => [
                'amount' => round($currentAmount, 2),
                'count' => $currentCount,
            ],
            'overdue' => [
                'amount' => round($overdueAmount, 2),
                'count' => $overdueCount,
            ],
            'total' => [
                'amount' => round($currentAmount + $overdueAmount, 2),
                'count' => $currentCount + $overdueCount,
            ],
        ];
    }

    /**
     * Get sales tax data with comparison to previous period.
     *
     * @return array<string, float|int>
     */
    private function getSalesTax(Carbon $startDate, Carbon $endDate, Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        $currentTax = (float) Invoice::query()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->sum('tax_amount');

        $previousTax = (float) Invoice::query()
            ->whereBetween('issue_date', [$previousStartDate, $previousEndDate])
            ->whereNotIn('status', ['cancelled'])
            ->sum('tax_amount');

        $changePercentage = $this->calculatePercentageChange($previousTax, $currentTax);

        return [
            'amount' => round($currentTax, 2),
            'previous_amount' => round($previousTax, 2),
            'change_percentage' => $changePercentage,
        ];
    }

    /**
     * Get unique products sold count with comparison to previous period.
     *
     * @return array<string, float|int>
     */
    private function getProductsSold(Carbon $startDate, Carbon $endDate, Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        $currentCount = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.workspace_id', Auth::user()?->current_workspace_id)
            ->whereBetween('invoices.issue_date', [$startDate, $endDate])
            ->whereNotIn('invoices.status', ['cancelled'])
            ->whereNotNull('invoice_items.product_id')
            ->distinct('invoice_items.product_id')
            ->count('invoice_items.product_id');

        $previousCount = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.workspace_id', Auth::user()?->current_workspace_id)
            ->whereBetween('invoices.issue_date', [$previousStartDate, $previousEndDate])
            ->whereNotIn('invoices.status', ['cancelled'])
            ->whereNotNull('invoice_items.product_id')
            ->distinct('invoice_items.product_id')
            ->count('invoice_items.product_id');

        return [
            'count' => $currentCount,
            'previous_count' => $previousCount,
            'change_percentage' => $this->calculatePercentageChange((float) $previousCount, (float) $currentCount),
        ];
    }

    /**
     * Get unique customers with sales count with comparison to previous period.
     *
     * @return array<string, float|int>
     */
    private function getCustomersWithSales(Carbon $startDate, Carbon $endDate, Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        $currentCount = Invoice::query()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled'])
            ->distinct('contact_id')
            ->count('contact_id');

        $previousCount = Invoice::query()
            ->whereBetween('issue_date', [$previousStartDate, $previousEndDate])
            ->whereNotIn('status', ['cancelled'])
            ->distinct('contact_id')
            ->count('contact_id');

        return [
            'count' => $currentCount,
            'previous_count' => $previousCount,
            'change_percentage' => $this->calculatePercentageChange((float) $previousCount, (float) $currentCount),
        ];
    }

    /**
     * Get prescriptions created count with comparison to previous period.
     *
     * @return array<string, float|int>
     */
    private function getPrescriptionsCreated(Carbon $startDate, Carbon $endDate, Carbon $previousStartDate, Carbon $previousEndDate): array
    {
        $currentCount = Prescription::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $previousCount = Prescription::query()
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        return [
            'count' => $currentCount,
            'previous_count' => $previousCount,
            'change_percentage' => $this->calculatePercentageChange((float) $previousCount, (float) $currentCount),
        ];
    }

    /**
     * Get workflows summary with pending and overdue jobs count.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getWorkflowsSummary(): array
    {
        return Workflow::query()
            ->withCount([
                'stages as pending_jobs_count' => function ($query) {
                    $query->join('workflow_jobs', 'workflow_stages.id', '=', 'workflow_jobs.workflow_stage_id')
                        ->where('workflow_jobs.completed_at', null);
                },
                'stages as overdue_jobs_count' => function ($query) {
                    $query->join('workflow_jobs', 'workflow_stages.id', '=', 'workflow_jobs.workflow_stage_id')
                        ->where('workflow_jobs.completed_at', null)
                        ->where('workflow_jobs.due_date', '<', now());
                },
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Workflow $workflow) => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'is_active' => $workflow->is_active,
                'pending_jobs_count' => $workflow->pending_jobs_count ?? 0,
                'overdue_jobs_count' => $workflow->overdue_jobs_count ?? 0,
            ])
            ->toArray();
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange(float $previous, float $current): float
    {
        if ($previous === 0.0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
