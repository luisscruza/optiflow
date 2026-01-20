<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MarkAllNotificationsAsReadController extends Controller
{
    /**
     * Mark all notifications as read.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
