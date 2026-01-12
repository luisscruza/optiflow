<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProductAction;
use App\Http\Requests\CreateProductRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class QuickProductCreate extends Controller
{
    /**
     * Handle the incoming request.
     */
    /**
     * Store a newly created resource in storage.
     */
    public function __invoke(CreateProductRequest $request, CreateProductAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        $product = $action->handle($user, $request->validated());

        session()->flash('newly_created_product', $product);

        return redirect()->back()
            ->with('success', 'Product creado correctamente.');
    }
}
