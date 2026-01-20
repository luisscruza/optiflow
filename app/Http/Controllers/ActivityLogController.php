<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

final class ActivityLogController
{
    /**
     * Display a listing of all activity logs.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->get('subject_type'), function ($query, $subjectType) {
                $query->where('subject_type', $subjectType);
            })
            ->when($request->get('subject_id'), function ($query, $subjectId) {
                $query->where('subject_id', $subjectId);
            })
            ->when($request->get('causer_id'), function ($query, $causerId) {
                $query->where('causer_id', $causerId);
            })
            ->when($request->get('event'), function ($query, $event) {
                $query->where('event', $event);
            })
            ->latest()
            ->paginate(30);

        return Inertia::render('activities/index', [
            'activities' => $activities,
            'filters' => [
                'subject_type' => $request->get('subject_type'),
                'subject_id' => $request->get('subject_id'),
                'causer_id' => $request->get('causer_id'),
                'event' => $request->get('event'),
            ],
        ]);
    }

    /**
     * Display activity logs for a specific model.
     */
    public function show(string $model, int $id, #[CurrentUser] User $user): Response
    {
        $modelClass = "App\\Models\\{$model}";

        if (! class_exists($modelClass)) {
            abort(404, 'Model not found.');
        }

        $activities = Activity::query()
            ->where('subject_type', $modelClass)
            ->where('subject_id', $id)
            ->with('causer')
            ->latest()
            ->get();

        return Inertia::render('activities/show', [
            'activities' => $activities,
            'model' => $model,
            'modelId' => $id,
        ]);
    }
}
