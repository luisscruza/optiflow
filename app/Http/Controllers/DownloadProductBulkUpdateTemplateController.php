<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateProductBulkUpdateTemplateAction;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadProductBulkUpdateTemplateController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, GenerateProductBulkUpdateTemplateAction $action): StreamedResponse
    {
        abort_unless($user->can(Permission::ProductsEdit) && $user->can(Permission::InventoryAdjust), 403);

        return $action->handle($user);
    }
}
