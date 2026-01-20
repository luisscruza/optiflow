<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteNotificationAction
{
    public function handle(User $user, string $notificationId): void
    {
        DB::transaction(function () use ($user, $notificationId): void {
            $user->notifications()
                ->where('id', $notificationId)
                ->delete();
        });
    }
}
