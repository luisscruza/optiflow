<?php

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Report::query()->delete();

        $reports = ReportType::cases();

        foreach ($reports as $reportType) {

            if (! $reportType->implemented()) {
                continue;
            }
            
            Report::create([
                'type' => $reportType,
                'name' => $reportType->label(),
                'description' => $reportType->description(),
                'group' => $reportType->group(),
                'is_active' => true,
                'config' => [],
            ]);
        }
    }
};
