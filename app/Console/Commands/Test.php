<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Workflow;
use App\Models\WorkflowJob;
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
        $workflow = Workflow::first();

        for ($i = 0; $i < 50000; $i++) {
            WorkflowJob::create([
                'workflow_id' => $workflow->id,
                'workflow_stage_id' => $workflow->stages()->inRandomOrder()->first()->id,
                'invoice_id' => Invoice::inRandomOrder()->first()->id,
                'contact_id' => Contact::inRandomOrder()->first()->id,
                'prescription_id' => null,
                'workspace_id' => 1,
            ]);
        }
    }
}
