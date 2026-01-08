<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Database\Seeder;

final class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reports = [];

        foreach (ReportType::cases() as $type) {
            $reports[] = [
                'type' => $type->value,
                'name' => $type->label(),
                'description' => $type->description(),
                'group' => $type->group()->value,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Report::insert($reports);
    }
}
