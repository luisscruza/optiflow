<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Salesman;
use Illuminate\Support\Facades\DB;

final readonly class UpdateSalesmanAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Salesman $salesman, array $data): Salesman
    {
        return DB::transaction(function () use ($salesman, $data): Salesman {
            $salesman->update($data);

            return $salesman;
        });
    }
}
