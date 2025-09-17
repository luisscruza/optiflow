<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCurrencyRateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class CurrencyRateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCurrencyRateAction $action, Request $request): RedirectResponse
    {

        $validated = request()->validate([
            'rate' => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
        ]);

        try {
            $currency = request()->route('currency');

            $validated['currency_id'] = $currency;
            $validated['date'] = now();

            $action->handle($validated);

            return redirect()->route('currencies.index')
                ->with('success', 'Tasa de cambio agregada correctamente.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['effective_date' => $e->getMessage()]);
        }
    }
}
