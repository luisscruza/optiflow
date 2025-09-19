<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\InvoiceResult;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateInvoiceAction
{
    public function __construct(private UpdateDocumentItemAction $UpdateItems)
    {
        //
    }

    /**
     * @throws Throwable
     */
    public function handle(Workspace $workspace, array $data): InvoiceResult
    {
        return DB::transaction(function () {

            //
        });
    }
}
