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
        $reportType = ReportType::WorkflowSummary;

        Report::query()->updateOrCreate(
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Report::query()
            ->where('type', ReportType::WorkflowSummary)
            ->delete();
    }
};
