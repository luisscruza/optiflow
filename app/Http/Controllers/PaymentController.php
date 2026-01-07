<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePaymentAction;
use App\Actions\DeletePaymentAction;
use App\Actions\UpdatePaymentAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\Permission;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentConcept;
use App\Models\Tax;
use App\Models\User;
use App\Models\WithholdingType;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PaymentsView), 403);

        $query = Payment::query()
            ->with(['bankAccount', 'currency', 'invoice.contact', 'contact'])
            ->completed()
            ->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                        $contactQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice.contact', function ($contactQuery) use ($search): void {
                        $contactQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->get('payment_type'));
        }

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->get('bank_account_id'));
        }

        $payments = $query->paginate(30)->withQueryString();

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'filters' => [
                'search' => $request->get('search'),
                'payment_type' => $request->get('payment_type'),
                'bank_account_id' => $request->get('bank_account_id'),
            ],
            'paymentTypes' => PaymentType::options(),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PaymentsCreate), 403);

        $pendingInvoices = Invoice::query()
            ->with(['contact', 'documentSubtype'])
            ->whereColumn('total_amount', '>', 'paid_amount')
            ->where('status', '!=', 'voided')
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                $invoice->amount_due = $invoice->total_amount - $invoice->paid_amount;

                return $invoice;
            });

        return Inertia::render('payments/create', [
            'pendingInvoices' => $pendingInvoices,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
            'paymentTypes' => PaymentType::options(),
            'chartAccounts' => ChartAccount::query()
                ->active()
                ->whereIn('type', ['income', 'asset', 'liability'])
                ->orderBy('code')
                ->get(),
            'paymentConcepts' => PaymentConcept::query()
                ->active()
                ->with('chartAccount')
                ->orderBy('name')
                ->get(),
            'withholdingTypes' => WithholdingType::query()
                ->active()
                ->orderBy('name')
                ->get(),
            'taxes' => Tax::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request, #[CurrentUser] User $user, CreatePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsCreate), 403);

        $validatedData = $request->validated();

        $invoice = null;
        if (! empty($validatedData['invoice_id'])) {
            $invoice = Invoice::query()->find($validatedData['invoice_id']);
        }

        $payment = $action->handle($invoice, $validatedData);

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pago registrado correctamente.');
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PaymentsView), 403);

        $payment->load([
            'bankAccount.currency',
            'currency',
            'invoice.contact',
            'invoice.documentSubtype',
            'contact',
            'lines.chartAccount',
            'lines.paymentConcept',
            'lines.tax',
            'withholdings.withholdingType',
        ]);

        return Inertia::render('payments/show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit(Payment $payment, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PaymentsEdit), 403);

        $payment->load([
            'bankAccount',
            'currency',
            'invoice.contact',
            'contact',
            'lines.chartAccount',
            'lines.paymentConcept',
            'lines.tax',
            'withholdings.withholdingType',
        ]);

        return Inertia::render('payments/edit', [
            'payment' => $payment,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'bankAccounts' => BankAccount::onlyActive()->with('currency')->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::options(),
            'paymentTypes' => PaymentType::options(),
            'chartAccounts' => ChartAccount::query()
                ->active()
                ->whereIn('type', ['income', 'asset', 'liability'])
                ->orderBy('code')
                ->get(),
            'paymentConcepts' => PaymentConcept::query()
                ->active()
                ->with('chartAccount')
                ->orderBy('name')
                ->get(),
            'withholdingTypes' => WithholdingType::query()
                ->active()
                ->orderBy('name')
                ->get(),
            'taxes' => Tax::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment, #[CurrentUser] User $user, UpdatePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsEdit), 403);

        $action->handle($payment, $request->validated());

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'Pago actualizado correctamente.');
    }

    /**
     * Void the specified payment.
     */
    public function destroy(Payment $payment, #[CurrentUser] User $user, DeletePaymentAction $action): RedirectResponse
    {
        abort_unless($user->can(Permission::PaymentsDelete), 403);

        $action->handle($payment);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Pago anulado correctamente.');
    }
}
