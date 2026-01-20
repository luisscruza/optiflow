<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReportGroup;
use App\Models\Report;
use Inertia\Inertia;
use Inertia\Response;

final class ReportGroupController
{
    public function show(string $group): Response
    {
        $reportGroup = ReportGroup::from($group);

        $reports = Report::query()
            ->where('group', $reportGroup)
            ->where('is_active', true)
            ->get()
            ->map(fn (Report $report): array => [
                'id' => $report->id,
                'type' => $report->type->value,
                'name' => $report->name,
                'description' => $report->description,
                'group' => $report->group->value,
            ]);

        return Inertia::render('reports/group', [
            'group' => [
                'value' => $reportGroup->value,
                'label' => $reportGroup->label(),
            ],
            'reports' => $reports,
        ]);
    }
}
