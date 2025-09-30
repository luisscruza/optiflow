<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ProcessProductImportAction;
use App\Models\ProductImport;
use App\Models\Workspace;
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
    public function handle()
    {
        $stockMapping = [
            '1' => [
                'quantity' => 'Cantidad inicial en almacen: COVI Principal',
                'minimum_quantity' => 'Cantidad minima en almacen: COVI Principal',
                'maximum_quantity' => 'Cantidad máxima en almacen: COVI Principal',
            ],
        ];

        $productImport = ProductImport::first();

        $workspaces = Workspace::all();

        $process = app(ProcessProductImportAction::class)->handle($productImport, $workspaces, $stockMapping);

        dd($process);
    }
}
