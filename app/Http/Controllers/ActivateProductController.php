<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class ActivateProductController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Product $product, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ProductsEdit), 403);

        $product->update(['is_active' => true]);

        return redirect()->back()
            ->with('success', 'Producto activado correctamente.');
    }
}
