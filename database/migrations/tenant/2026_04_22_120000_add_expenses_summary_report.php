<?php

declare(strict_types=1);

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $type = ReportType::ExpensesSummary;

        Report::query()->updateOrCreate(
            ['type' => $type],
            [
                'name' => $type->label(),
                'description' => $type->description(),
                'group' => $type->group(),
                'is_active' => true,
                'config' => [],
            ]
        );
    }
};
