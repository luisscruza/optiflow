<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Jobs\RecalculateBankAccount;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WithholdingType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreatePaymentAction
{
    /**
     * Execute the action.
     */
    public function handle(?Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data): Payment {
            $paymentType = PaymentType::from($data['payment_type']);
            $account = BankAccount::query()->findOrFail($data['bank_account_id']);

            if ($paymentType === PaymentType::InvoicePayment) {
                return $this->createInvoicePayment($invoice, $data, $account);
            }

            return $this->createOtherIncomePayment($data, $account);
        });
    }

    /**
     * Create a payment for an invoice.
     */
    private function createInvoicePayment(?Invoice $invoice, array $data, BankAccount $account): Payment
    {
        if (! $invoice) {
            throw new InvalidArgumentException('Invoice is required for invoice payments.');
        }

        if ($invoice->amount_due < $data['amount']) {
            throw new InvalidArgumentException('Payment amount exceeds the amount due on the invoice.');
        }

        $payment = Payment::query()->create([
            'payment_type' => PaymentType::InvoicePayment,
            'payment_number' => $this->generatePaymentNumber(),
            'invoice_id' => $invoice->id,
            'bank_account_id' => $data['bank_account_id'],
            'currency_id' => $account->currency_id,
            'amount' => $data['amount'],
            'subtotal_amount' => $data['amount'],
            'tax_amount' => 0,
            'withholding_amount' => 0,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
            'status' => PaymentStatus::Completed,
        ]);

        RecalculateBankAccount::dispatch($account);

        return $payment;
    }

    /**
     * Create a payment for other income (non-invoice).
     */
    private function createOtherIncomePayment(array $data, BankAccount $account): Payment
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

        $payment = Payment::query()->create([
            'payment_type' => PaymentType::OtherIncome,
            'payment_number' => $this->generatePaymentNumber(),
            'contact_id' => $data['contact_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'],
            'currency_id' => $account->currency_id,
            'amount' => $totalAmount,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxTotal,
            'withholding_amount' => $withholdingTotal,
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'note' => $data['note'] ?? null,
            'status' => PaymentStatus::Completed,
        ]);

        // Create lines
        foreach ($data['lines'] as $index => $line) {
            $lineSubtotal = $line['quantity'] * $line['unit_price'];
            $lineTax = isset($line['tax_id']) && $line['tax_id']
                ? $this->calculateLineTax($lineSubtotal, $line['tax_id'])
                : 0;

            $payment->lines()->create([
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
            ]);
        }

        // Create withholdings
        if (! empty($data['withholdings'])) {
            foreach ($data['withholdings'] as $withholding) {
                $withholdingType = WithholdingType::query()->find($withholding['withholding_type_id']);
                if ($withholdingType) {
                    $payment->withholdings()->create([
                        'withholding_type_id' => $withholding['withholding_type_id'],
                        'base_amount' => $withholding['base_amount'],
                        'percentage' => $withholdingType->percentage,
                        'amount' => $withholdingType->calculateAmount($withholding['base_amount']),
                    ]);
                }
            }
        }

        RecalculateBankAccount::dispatch($account);

        return $payment;
    }

    /**
     * Calculate tax amount for a line.
     */
    private function calculateLineTax(float $subtotal, int $taxId): float
    {
        $tax = \App\Models\Tax::query()->find($taxId);

        return $tax ? round($subtotal * ($tax->rate / 100), 2) : 0;
    }

    /**
     * Generate a unique payment number.
     */
    private function generatePaymentNumber(): string
    {
        $lastPayment = Payment::query()
            ->select(['id', 'payment_number'])
            ->whereNotNull('payment_number')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastPayment && ! empty($lastPayment->getAttribute('payment_number'))) {
            $paymentNumber = $lastPayment->getAttribute('payment_number');
            $lastNumber = (int) mb_substr($paymentNumber, 4);
        }

        return 'PAG-'.mb_str_pad((string) ($lastNumber + 1), 6, '0', STR_PAD_LEFT);
    }
}
