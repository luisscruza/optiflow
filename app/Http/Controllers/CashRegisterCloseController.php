<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Models\CompanyDetail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashRegisterCloseController
{
    /**
     * Stream the cash register close (cierre de caja) PDF for a given date.
     */
    public function __invoke(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PaymentsView), 403);

        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = Carbon::parse($request->input('date'));

        // ── Workspace-scoped invoice IDs ──
        // Invoice uses BelongsToWorkspace which auto-scopes to current workspace.
        $workspaceInvoiceIds = Invoice::query()->pluck('id');

        // ── Payments for the day (scoped to workspace invoices) ──
        $payments = Payment::query()
            ->with(['bankAccount', 'currency', 'invoice.contact', 'contact'])
            ->where('status', PaymentStatus::Completed->value)
            ->whereDate('payment_date', $date)
            ->whereIn('invoice_id', $workspaceInvoiceIds)
            ->orderBy('payment_method')
            ->orderBy('created_at')
            ->get();

        // ── Group by payment method ──
        $byPaymentMethod = $payments->groupBy(fn (Payment $payment): string => $payment->payment_method->value);

        $paymentMethodSummary = [];
        foreach (PaymentMethod::cases() as $method) {
            $methodPayments = $byPaymentMethod->get($method->value, collect());
            if ($methodPayments->isNotEmpty()) {
                $paymentMethodSummary[] = [
                    'label' => $method->label(),
                    'count' => $methodPayments->count(),
                    'total' => $methodPayments->sum('amount'),
                ];
            }
        }

        // ── Group by bank account ──
        $byBankAccount = $payments->groupBy('bank_account_id');
        $bankAccountSummary = [];
        foreach ($byBankAccount as $paymentsGroup) {
            $bankAccount = $paymentsGroup->first()->bankAccount;
            $bankAccountSummary[] = [
                'name' => $bankAccount?->name ?? 'Sin cuenta',
                'count' => $paymentsGroup->count(),
                'total' => $paymentsGroup->sum('amount'),
            ];
        }

        // ── Group by contact (client) ──
        $byContact = $payments->filter(fn (Payment $p): bool => $p->contact_id !== null || ($p->invoice && $p->invoice->contact_id !== null))
            ->groupBy(function (Payment $p): int {
                return $p->contact_id ?? $p->invoice->contact_id;
            });

        $contactSummary = [];
        foreach ($byContact as $paymentsGroup) {
            $firstPayment = $paymentsGroup->first();
            $contact = $firstPayment->contact ?? $firstPayment->invoice?->contact;
            $contactSummary[] = [
                'name' => $contact?->name ?? 'Sin cliente',
                'count' => $paymentsGroup->count(),
                'total' => $paymentsGroup->sum('amount'),
            ];
        }

        usort($contactSummary, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $grandTotal = $payments->sum('amount');

        // ── Invoices of the day (workspace-scoped automatically) ──
        $invoicesOfDay = Invoice::query()
            ->with(['contact', 'payments'])
            ->whereDate('issue_date', $date)
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Deleted->value, InvoiceStatus::Cancelled->value])
            ->orderBy('document_number')
            ->get();

        $totalInvoiced = $invoicesOfDay->sum('total_amount');
        $totalPaidOnInvoices = $invoicesOfDay->sum(fn (Invoice $invoice): float => (float) $invoice->payments->sum('amount'));
        $totalPending = max(0, $totalInvoiced - $totalPaidOnInvoices);

        // ── Build invoice summary with pending balances ──
        $invoiceSummary = $invoicesOfDay->map(fn (Invoice $invoice): array => [
            'document_number' => $invoice->document_number,
            'contact_name' => $invoice->contact?->name ?? 'Sin cliente',
            'status' => $invoice->status->label(),
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => (float) $invoice->payments->sum('amount'),
            'pending_amount' => max(0, (float) $invoice->total_amount - (float) $invoice->payments->sum('amount')),
        ])->all();

        $pdf = Pdf::loadView('cash-register-close.pdf', [
            'company' => CompanyDetail::getAll(),
            'workspaceName' => $user->currentWorkspace?->name ?? 'Sin sucursal',
            'date' => $date,
            'payments' => $payments,
            'paymentMethodSummary' => $paymentMethodSummary,
            'bankAccountSummary' => $bankAccountSummary,
            'contactSummary' => $contactSummary,
            'grandTotal' => $grandTotal,
            'invoiceSummary' => $invoiceSummary,
            'totalInvoiced' => $totalInvoiced,
            'totalPaidOnInvoices' => $totalPaidOnInvoices,
            'totalPending' => $totalPending,
            'generatedBy' => $user->name,
        ])->setPaper('a4', 'portrait');

        $filename = 'cierre-de-caja-'.$date->format('Y-m-d').'.pdf';

        return $pdf->stream($filename);
    }
}
