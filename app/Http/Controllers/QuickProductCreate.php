<?php

namespace App\Http\Controllers;

use App\Actions\CreateProductAction;
use App\Http\Requests\CreateProductRequest;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use App\Models\User;

class QuickProductCreate extends Controller
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
            ->with('success', 'Product created successfully.');
    }
}
