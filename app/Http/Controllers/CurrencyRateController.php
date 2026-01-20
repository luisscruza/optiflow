<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCurrencyRateAction;
use App\Http\Requests\CreateCurrencyRateRequest;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

final class CurrencyRateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCurrencyRateAction $action, CreateCurrencyRateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $currency = $request->route('currency');

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
