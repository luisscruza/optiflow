<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $filter = $request->query('filter', 'all');

        $notifications = match ($filter) {
            'unread' => $user->unreadNotifications()->paginate(15),
            default => $user->notifications()->paginate(15),
        };

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'filter' => $filter,
        ]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->where('id', $id)
            ->delete();

        return back();
    }
}
