<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateTaxAction;
use App\Actions\DeleteTaxAction;
use App\Actions\UpdateTaxAction;
use App\Http\Requests\CreateTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $taxes = Tax::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('taxes/index', [
            'taxes' => $taxes,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('taxes/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(#[CurrentUser] User $user, CreateTaxRequest $request, CreateTaxAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return redirect()
            ->route('taxes.index')
            ->with('success', 'Tax created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tax $tax): Response
    {
        $tax->loadCount(['products', 'invoiceItems']);

        return Inertia::render('taxes/show', [
            'tax' => $tax,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tax $tax): Response
    {
        return Inertia::render('taxes/edit', [
            'tax' => $tax,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(#[CurrentUser] User $user, UpdateTaxRequest $request, Tax $tax, UpdateTaxAction $action): RedirectResponse
    {
        $action->handle($user, $tax, $request->validated());

        return redirect()
            ->route('taxes.index')
            ->with('success', 'Tax updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tax $tax, DeleteTaxAction $action): RedirectResponse
    {
        try {
            $action->handle(Auth::user(), $tax);

            return redirect()
                ->route('taxes.index')
                ->with('success', 'Tax deleted successfully.');
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('taxes.index')
                ->with('error', $e->getMessage());
        }
    }
}
