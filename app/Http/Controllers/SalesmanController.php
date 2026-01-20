<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateSalesmanAction;
use App\Actions\DeleteSalesmanAction;
use App\Actions\UpdateSalesmanAction;
use App\Http\Requests\StoreSalesmanRequest;
use App\Http\Requests\UpdateSalesmanRequest;
use App\Models\Salesman;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SalesmanController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Salesman::query()
            ->with('user')
            ->orderBy('name')
            ->orderBy('surname');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        $salesmen = $query->paginate(30)->withQueryString();

        return Inertia::render('salesmen/index', [
            'salesmen' => $salesmen,
            'filters' => [
                'search' => $request->get('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('salesmen/create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSalesmanRequest $request, CreateSalesmanAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('salesmen.index')
            ->with('success', 'Vendedor creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Salesman $salesman): Response
    {
        $salesman->load('user');

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('salesmen/edit', [
            'salesman' => $salesman,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalesmanRequest $request, Salesman $salesman, UpdateSalesmanAction $action): RedirectResponse
    {
        $action->handle($salesman, $request->validated());

        return redirect()->route('salesmen.index')
            ->with('success', 'Vendedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Salesman $salesman, DeleteSalesmanAction $action): RedirectResponse
    {
        $action->handle($salesman);

        return redirect()->route('salesmen.index')
            ->with('success', 'Vendedor eliminado exitosamente.');
    }
}
