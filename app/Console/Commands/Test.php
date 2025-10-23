<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
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
        $invoice = Invoice::query()->first();

        $user = User::query()->first();

        $invoice->commentAsUser($user, 'This is a test comment from the command line.');
    }
}
