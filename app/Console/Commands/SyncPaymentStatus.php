<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync payment status command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoices = Invoice::query()->whereHas('payments')->get();

        foreach ($invoices as $invoice) {
            $invoice->updatePaymentStatus();
        }
    }
}
