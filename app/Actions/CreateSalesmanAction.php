<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Salesman;
use Illuminate\Support\Facades\DB;

final readonly class CreateSalesmanAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Salesman
    {
        return DB::transaction(function () use ($data): Salesman {
            return Salesman::query()->create($data);
        });
    }
}
