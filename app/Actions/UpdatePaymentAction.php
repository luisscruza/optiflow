<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentType;
use App\Exceptions\ReportableActionException;
use App\Jobs\RecalculateBankAccount;
use App\Models\BankAccount;
use App\Models\Payment;
use App\Models\Tax;
use App\Models\WithholdingType;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePaymentAction
{
    /**
     * Execute the action.
     */
    public function handle(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data): Payment {
            $oldBankAccount = $payment->bankAccount;
            $newBankAccount = BankAccount::query()->findOrFail($data['bank_account_id']);

            if ($payment->payment_type === PaymentType::InvoicePayment) {
                $this->updateInvoicePayment($payment, $data, $newBankAccount);
            } else {
                $this->updateOtherIncomePayment($payment, $data, $newBankAccount);
            }

            // Recalculate both old and new bank accounts if they changed
            if ($oldBankAccount->id !== $newBankAccount->id) {
                RecalculateBankAccount::dispatch($oldBankAccount);
            }
            RecalculateBankAccount::dispatch($newBankAccount);

            return $payment->fresh();
        });
    }

    /**
     * Update an invoice payment.
     */
    private function updateInvoicePayment(Payment $payment, array $data, BankAccount $newBankAccount): void
    {
        $invoice = $payment->invoice;

        if ($invoice) {
            $otherPaymentsTotal = $invoice->payments()
                ->where('id', '!=', $payment->id)
                ->sum('amount');

            $newAmountDue = $invoice->total_amount - $otherPaymentsTotal;

            if ($newAmountDue < $data['amount']) {
                throw new ReportableActionException('El monto del pago excede el monto adeudado en la factura después de esta actualización.');
            }
        }

        $payment->update([
            'bank_account_id' => $data['bank_account_id'],
            'currency_id' => $newBankAccount->currency_id,
            'amount' => $data['amount'],
            'subtotal_amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
        ]);
    }

    /**
     * Update an other income payment.
     */
    private function updateOtherIncomePayment(Payment $payment, array $data, BankAccount $newBankAccount): void
    {
        // Calculate totals from lines
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($data['lines'] as $line) {
            $lineSubtotal = $line['quantity'] * $line['unit_price'];
            $lineTax = isset($line['tax_id']) && $line['tax_id']
                ? $this->calculateLineTax($lineSubtotal, $line['tax_id'])
                : 0;

            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;
        }

        // Calculate withholdings
        $withholdingTotal = 0;
        if (! empty($data['withholdings'])) {
            foreach ($data['withholdings'] as $withholding) {
                $withholdingType = WithholdingType::query()->find($withholding['withholding_type_id']);
                if ($withholdingType) {
                    $withholdingTotal += $withholdingType->calculateAmount($withholding['base_amount']);
                }
            }
        }

        $totalAmount = $subtotal + $taxTotal - $withholdingTotal;

        $payment->update([
            'contact_id' => $data['contact_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'],
            'currency_id' => $newBankAccount->currency_id,
            'amount' => $totalAmount,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxTotal,
            'withholding_amount' => $withholdingTotal,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
        ]);

        // Sync lines
        $existingLineIds = collect($data['lines'])
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete removed lines
        $payment->lines()
            ->whereNotIn('id', $existingLineIds)
            ->delete();

        // Update or create lines
        foreach ($data['lines'] as $index => $line) {
            $lineSubtotal = $line['quantity'] * $line['unit_price'];
            $lineTax = isset($line['tax_id']) && $line['tax_id']
                ? $this->calculateLineTax($lineSubtotal, $line['tax_id'])
                : 0;

            $lineData = [
                'payment_concept_id' => $line['payment_concept_id'] ?? null,
                'chart_account_id' => $line['chart_account_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $lineSubtotal,
                'tax_id' => $line['tax_id'] ?? null,
                'tax_amount' => $lineTax,
                'total' => $lineSubtotal + $lineTax,
                'sort_order' => $index,
            ];

            if (! empty($line['id'])) {
                $payment->lines()->where('id', $line['id'])->update($lineData);
            } else {
                $payment->lines()->create($lineData);
            }
        }

        // Sync withholdings
        $existingWithholdingIds = collect($data['withholdings'] ?? [])
            ->pluck('id')
            ->filter()
            ->toArray();

        // Delete removed withholdings
        $payment->withholdings()
            ->whereNotIn('id', $existingWithholdingIds)
            ->delete();

        // Update or create withholdings
        foreach ($data['withholdings'] ?? [] as $withholding) {
            $withholdingType = WithholdingType::query()->find($withholding['withholding_type_id']);
            if (! $withholdingType) {
                continue;
            }

            $withholdingData = [
                'withholding_type_id' => $withholding['withholding_type_id'],
                'base_amount' => $withholding['base_amount'],
                'percentage' => $withholdingType->percentage,
                'amount' => $withholdingType->calculateAmount($withholding['base_amount']),
            ];

            if (! empty($withholding['id'])) {
                $payment->withholdings()->where('id', $withholding['id'])->update($withholdingData);
            } else {
                $payment->withholdings()->create($withholdingData);
            }
        }
    }

    /**
     * Calculate tax amount for a line.
     */
    private function calculateLineTax(float $subtotal, int $taxId): float
    {
        $tax = Tax::query()->find($taxId);

        return $tax ? round($subtotal * ($tax->rate / 100), 2) : 0;
    }
}
