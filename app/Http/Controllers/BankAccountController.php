<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateBankAccountAction;
use App\Actions\DeleteBankAccountAction;
use App\Actions\UpdateBankAccountAction;
use App\Enums\BankAccountType;
use App\Exceptions\ActionValidationException;
use App\Http\Requests\CreateBankAccountRequest;
use App\Http\Requests\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class BankAccountController extends Controller
{
    /**
     * Display a listing of bank accounts.
     */
    public function index(): Response
    {
        $bankAccounts = BankAccount::with(['currency', 'payments' => function ($query): void {
            $query->latest('payment_date')->limit(5);
        }])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($account): array {
                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'type_label' => $account->type->label(),
                    'account_number' => $account->account_number,
                    'currency' => $account->currency,
                    'balance' => (float) $account->balance,
                    'initial_balance' => (float) $account->initial_balance,
                    'initial_balance_date' => $account->initial_balance_date,
                    'is_active' => $account->is_active,
                    'is_system_account' => $account->is_system_account,
                    'description' => $account->description,
                    'recent_payments' => $account->payments->map(fn ($payment): array => [
                        'id' => $payment->id,
                        'amount' => (float) $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method->value,
                    ]),
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at,
                ];
            });

        return Inertia::render('bank-accounts/index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    /**
     * Show the form for creating a new bank account.
     */
    public function create(): Response
    {
        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $accountTypes = collect(BankAccountType::cases())->map(fn ($type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ]);

        return Inertia::render('bank-accounts/create', [
            'currencies' => $currencies,
            'accountTypes' => $accountTypes,
        ]);
    }

    /**
     * Store a newly created bank account.
     */
    public function store(CreateBankAccountRequest $request, CreateBankAccountAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Cuenta bancaria creada correctamente.');
    }

    /**
     * Display the specified bank account.
     */
    public function show(BankAccount $bankAccount): Response
    {
        $bankAccount->load([
            'currency',
            'payments' => function ($query): void {
                $query->with(['currency', 'invoice.contact'])
                    ->latest('payment_date');
            },
        ]);

        return Inertia::render('bank-accounts/show', [
            'bankAccount' => [
                'id' => $bankAccount->id,
                'name' => $bankAccount->name,
                'type' => $bankAccount->type->value,
                'type_label' => $bankAccount->type->label(),
                'account_number' => $bankAccount->account_number,
                'currency' => $bankAccount->currency,
                'balance' => (float) $bankAccount->balance,
                'initial_balance' => (float) $bankAccount->initial_balance,
                'initial_balance_date' => $bankAccount->initial_balance_date,
                'description' => $bankAccount->description,
                'is_active' => $bankAccount->is_active,
                'is_system_account' => $bankAccount->is_system_account,
                'created_at' => $bankAccount->created_at,
                'updated_at' => $bankAccount->updated_at,
                'payments' => $bankAccount->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method->value,
                    'payment_method_label' => $payment->payment_method->label(),
                    'note' => $payment->note,
                    'currency' => $payment->currency,
                    'invoice' => $payment->invoice ? [
                        'id' => $payment->invoice->id,
                        'document_number' => $payment->invoice->document_number,
                        'contact' => $payment->invoice->contact,
                    ] : null,
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified bank account.
     */
    public function edit(BankAccount $bankAccount): Response|RedirectResponse
    {
        if ($bankAccount->is_system_account) {
            return redirect()->route('bank-accounts.index')
                ->with('error', 'No se puede editar una cuenta del sistema.');
        }

        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        $accountTypes = collect(BankAccountType::cases())->map(fn ($type): array => [
            'value' => $type->value,
            'label' => $type->label(),
            'description' => $type->description(),
        ]);

        return Inertia::render('bank-accounts/edit', [
            'bankAccount' => [
                'id' => $bankAccount->id,
                'name' => $bankAccount->name,
                'type' => $bankAccount->type->value,
                'account_number' => $bankAccount->account_number,
                'currency_id' => $bankAccount->currency_id,
                'description' => $bankAccount->description,
                'is_active' => $bankAccount->is_active,
            ],
            'currencies' => $currencies,
            'accountTypes' => $accountTypes,
        ]);
    }

    /**
     * Update the specified bank account.
     */
    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount, UpdateBankAccountAction $action): RedirectResponse
    {
        if ($bankAccount->is_system_account) {
            return back()->withErrors(['bank_account' => 'No se puede editar una cuenta del sistema.']);
        }

        $action->handle($bankAccount, $request->validated());

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Cuenta bancaria actualizada correctamente.');
    }

    /**
     * Remove the specified bank account.
     */
    public function destroy(BankAccount $bankAccount, DeleteBankAccountAction $action): RedirectResponse
    {
        try {
            $action->handle($bankAccount);
        } catch (ActionValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Cuenta bancaria eliminada correctamente.');
    }
}
