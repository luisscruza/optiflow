<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateDashboardLayoutAction
{
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'dashboard_layout' => $data['layout'],
            ]);
        });
    }
}
