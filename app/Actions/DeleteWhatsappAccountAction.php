<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\DB;

final class DeleteWhatsappAccountAction
{
    public function handle(WhatsappAccount $whatsappAccount): void
    {
        DB::transaction(function () use ($whatsappAccount): void {
            $whatsappAccount->delete();
        });
    }
}
