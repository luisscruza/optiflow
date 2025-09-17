<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCurrencyAction;
use App\Http\Requests\CreateCurrencyRequest;
use App\Models\Currency;
use App\Models\CurrencyRate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CurrencyController extends Controller
{
    /**
     * Display a listing of currencies.
     */
    public function index(): Response
    {
        $currencies = Currency::with(['rates' => function ($query) {
            $query->latest('effective_date')->limit(1);
        }])->get()->map(function ($currency) {
            $currentRate = $currency->getCurrentRate();
            $variation = $currency->rates()->count() > 1
                ? $currency->getVariation()
                : 0.0;

            return [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'is_default' => $currency->is_default,
                'current_rate' => (float) $currentRate,
                'rate_variation' => (float) $variation,
            ];
        });

        $defaultCurrency = Currency::where('is_default', true)->first();

        // Get historical rates for the last 30 days
        $historicalRates = CurrencyRate::with('currency')
            ->orderBy('effective_date', 'desc')
            ->get()
            ->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'currency_id' => $rate->currency_id,
                    'rate' => (float) $rate->rate,
                    'date' => $rate->effective_date->format('Y-m-d'),
                ];
            });

        return Inertia::render('currencies/index', [
            'currencies' => $currencies,
            'defaultCurrency' => $defaultCurrency,
            'historicalRates' => $historicalRates,
        ]);
    }

    /**
     * Show the form for creating a new currency.
     */
    public function create(): Response
    {
        return Inertia::render('currencies/create');
    }

    /**
     * Store a newly created currency.
     */
    public function store(CreateCurrencyRequest $request, CreateCurrencyAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('currencies.index')
            ->with('success', 'Moneda creada correctamente.');
    }

    /**
     * Display the specified currency.
     */
    public function show(Currency $currency): Response
    {
        $currency->load(['rates' => function ($query) {
            $query->orderBy('effective_date', 'desc');
        }]);

        return Inertia::render('currencies/show', [
            'currency' => $currency,
        ]);
    }

    /**
     * Show the form for editing the specified currency.
     */
    public function edit(Currency $currency): Response
    {
        return Inertia::render('currencies/edit', [
            'currency' => $currency,
        ]);
    }

    /**
     * Update the specified currency.
     */
    public function update(CreateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $currency->update([
            'name' => $request->validated()['name'],
            'symbol' => $request->validated()['symbol'],
        ]);

        return redirect()->route('currencies.index')
            ->with('success', 'Moneda actualizada correctamente.');
    }

    /**
     * Remove the specified currency.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return back()->withErrors(['currency' => 'No se puede eliminar la moneda predeterminada.']);
        }

        $currency->delete();

        return redirect()->route('currencies.index')
            ->with('success', 'Moneda eliminada correctamente.');
    }
}
