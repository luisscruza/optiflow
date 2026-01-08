<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

final class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 't';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //     $this->info('Testing Invoice data...');

        //     // Simulate the dashboard query for workspace 1
        //     $workspaceId = 1;

        //     $startDate = \Carbon\Carbon::parse('2025-12-01')->startOfDay();
        //     $endDate = \Carbon\Carbon::parse('2026-01-04')->endOfDay();

        //     $this->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        //     $this->info("Workspace: {$workspaceId}");

        //     // Test query with workspace scope
        //     $invoices = Invoice::withoutGlobalScopes()
        //         ->where('workspace_id', $workspaceId)
        //         ->whereBetween('issue_date', [$startDate, $endDate])
        //         ->where('status', '!=', 'cancelled')
        //         ->get();

        //     $this->info("\nInvoices in range for workspace {$workspaceId}: ".$invoices->count());

        //     foreach ($invoices as $invoice) {
        //         $this->line("ID: {$invoice->id}, Date: {$invoice->issue_date}, Amount: {$invoice->total_amount}");
        //     }

        //     // Test the aggregation query
        //     $stats = Invoice::withoutGlobalScopes()
        //         ->where('workspace_id', $workspaceId)
        //         ->whereBetween('issue_date', [$startDate, $endDate])
        //         ->where('status', '!=', 'cancelled')
        //         ->selectRaw('
        //             COUNT(*) as total_invoices,
        //             SUM(total_amount) as total_sales,
        //             SUM(tax_amount) as total_tax
        //         ')
        //         ->first();

        //     $this->info("\nStats for workspace {$workspaceId}:");
        //     $this->line('Total invoices: '.($stats->total_invoices ?? 0));
        //     $this->line('Total sales: '.($stats->total_sales ?? 0));
        //     $this->line('Total tax: '.($stats->total_tax ?? 0));

        //     // Now check what the issue_date looks like
        //     $this->info("\n--- Raw date check ---");
        //     $rawInvoice = \Illuminate\Support\Facades\DB::table('invoices')->where('workspace_id', $workspaceId)->first();
        //     if ($rawInvoice) {
        //         $this->line('Raw issue_date type: '.gettype($rawInvoice->issue_date));
        //         $this->line('Raw issue_date value: '.$rawInvoice->issue_date);
        //     }
    }
}
