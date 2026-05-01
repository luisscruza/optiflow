<?php

declare(strict_types=1);

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $reportType = ReportType::InvoicesVsExpenses;

        Report::query()->firstOrCreate(
            ['type' => $reportType],
            [
                'name' => $reportType->label(),
                'description' => $reportType->description(),
                'group' => $reportType->group(),
                'is_active' => true,
                'config' => [],
            ]
        );
    }

    public function down(): void
    {
        Report::query()->where('type', ReportType::InvoicesVsExpenses)->delete();
    }
};
