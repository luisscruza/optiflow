import { Form } from '@inertiajs/react';
import { DollarSignIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BankAccount, type Invoice, type Payment } from '@/types';
import { useCurrency } from '@/utils/currency';

interface PaymentRegistrationModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: Invoice;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
    payment?: Payment | null;
}

export function PaymentRegistrationModal({ isOpen, onClose, invoice, bankAccounts, paymentMethods, payment = null }: PaymentRegistrationModalProps) {
    const [selectedBankAccount, setSelectedBankAccount] = useState<string>('');
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>('');
    const [amount, setAmount] = useState<string>('');
    const [note, setNote] = useState<string>('');
    const { format: formatCurrency } = useCurrency();
    const isEditing = payment !== null;

    // Initialize form with payment data when editing
    useEffect(() => {
        if (payment && isOpen) {
            setSelectedBankAccount(payment.bank_account_id.toString());
            setSelectedPaymentMethod(payment.payment_method);
            setAmount(payment.amount.toString());
            setNote(payment.note || '');
        } else if (!isOpen) {
            // Reset form when modal closes
            setSelectedBankAccount('');
            setSelectedPaymentMethod('');
            setAmount('');
            setNote('');
        }
    }, [payment, isOpen]);

    // Format today's datetime for the input default value (datetime-local format)
    const now = new Date();
    const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

    const handleFillFullAmount = () => {
        setAmount(invoice.amount_due.toString());
    };

    // Validation: Check if all required fields are filled
    const isFormValid = selectedBankAccount !== '' && selectedPaymentMethod !== '' && amount !== '' && parseFloat(amount) > 0;

    // Calculate remaining balance for partial payments
    const paymentAmount = amount !== '' ? parseFloat(amount) : 0;
    const remainingBalance = invoice.amount_due - paymentAmount;
    const isPartialPayment = paymentAmount > 0 && paymentAmount < invoice.amount_due;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-xl">
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <DollarSignIcon className="h-5 w-5" />
                        </div>
                        <span>{isEditing ? 'Editar pago' : 'Registrar pago'}</span>
                    </DialogTitle>
                </DialogHeader>

                {/* Invoice Summary Card - Redesigned to stand out */}
                <div className="rounded-lg border-2 border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                    <div className="space-y-3">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex-1 space-y-2">
                                <div>
                                    <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase">Factura</p>
                                    <p className="text-lg font-bold text-gray-900">{invoice.document_number}</p>
                                </div>
                                <div>
                                    <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase">Cliente</p>
                                    <p className="text-base font-semibold text-gray-900">{invoice.contact.name}</p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="mb-1 text-xs font-medium tracking-wide text-emerald-700 uppercase">Valor por cobrar</p>
                                <div className="rounded-lg border border-emerald-200 bg-white px-4 py-3 shadow-sm">
                                    <p className="text-2xl font-bold text-emerald-600">{formatCurrency(invoice.amount_due)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <Form
                    action={isEditing ? `/payments/${payment!.id}` : '/payments'}
                    method={isEditing ? 'patch' : 'post'}
                    resetOnSuccess
                    onSuccess={onClose}
                >
                    {({ processing, wasSuccessful, errors }) => (
                        <div className="space-y-5">
                            {!isEditing && <input type="hidden" name="invoice_id" value={invoice.id} />}
                            {/* Use current system date/time automatically */}
                            <input type="hidden" name="payment_date" value={today} />

                            <div className="space-y-5">
                                {/* Bank Account - Required */}
                                <div className="space-y-2">
                                    <Label htmlFor="bank_account_id" className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                        Cuenta bancaria
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select name="bank_account_id" value={selectedBankAccount} onValueChange={setSelectedBankAccount} required>
                                        <SelectTrigger
                                            className={`h-11 ${selectedBankAccount === '' ? 'border-gray-300' : 'border-emerald-300 bg-emerald-50/30'}`}
                                        >
                                            <SelectValue placeholder="Selecciona una cuenta" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {bankAccounts.map((account) => (
                                                <SelectItem key={account.id} value={account.id.toString()}>
                                                    {account.name} ({account.currency?.code || 'N/A'})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.bank_account_id && <p className="text-sm text-red-600">{errors.bank_account_id}</p>}
                                    {selectedBankAccount === '' && <p className="text-xs text-gray-500">Debes seleccionar una cuenta bancaria</p>}
                                </div>

                                {/* Amount - Required & Highlighted */}
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="amount" className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                            Valor del pago
                                            <span className="text-red-500">*</span>
                                        </Label>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleFillFullAmount}
                                            className="h-7 border-emerald-300 px-3 py-1 text-xs text-emerald-700 hover:bg-emerald-50"
                                        >
                                            Monto completo
                                        </Button>
                                    </div>
                                    <div className="relative">
                                        <Input
                                            id="amount"
                                            name="amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max={invoice.amount_due}
                                            value={amount}
                                            onChange={(e) => setAmount(e.target.value)}
                                            placeholder="0.00"
                                            className={`h-12 pl-14 text-lg font-semibold ${
                                                amount === '' ? 'border-gray-300' : 'border-emerald-300 bg-emerald-50/30 text-emerald-700'
                                            }`}
                                            required
                                        />
                                        <span className="absolute top-1/2 left-4 -translate-y-1/2 text-lg font-semibold text-gray-500">RD$</span>
                                    </div>
                                    {errors.amount && <p className="text-sm text-red-600">{errors.amount}</p>}
                                    {amount !== '' && parseFloat(amount) > invoice.amount_due && (
                                        <p className="text-sm text-amber-600">⚠️ El monto excede el valor por cobrar</p>
                                    )}
                                    {isPartialPayment && (
                                        <div className="flex items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                                            <span className="text-sm font-medium text-gray-500">Monto pendiente:</span>
                                            <span className="text-base font-bold text-gray-700">{formatCurrency(remainingBalance)}</span>
                                        </div>
                                    )}
                                    {amount === '' && <p className="text-xs text-gray-500">Ingresa el monto del pago</p>}
                                </div>

                                {/* Payment Method - Required */}
                                <div className="space-y-2">
                                    <Label htmlFor="payment_method" className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                        Método de pago
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select name="payment_method" value={selectedPaymentMethod} onValueChange={setSelectedPaymentMethod} required>
                                        <SelectTrigger
                                            className={`h-11 ${selectedPaymentMethod === '' ? 'border-gray-300' : 'border-emerald-300 bg-emerald-50/30'}`}
                                        >
                                            <SelectValue placeholder="Selecciona un método" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(paymentMethods).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.payment_method && <p className="text-sm text-red-600">{errors.payment_method}</p>}
                                    {selectedPaymentMethod === '' && <p className="text-xs text-gray-500">Debes seleccionar un método de pago</p>}
                                </div>

                                {/* Note */}
                                <div className="space-y-2">
                                    <Label htmlFor="note" className="text-sm font-semibold text-gray-900">
                                        Nota (opcional)
                                    </Label>
                                    <Input
                                        id="note"
                                        name="note"
                                        value={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        placeholder="Información adicional sobre el pago"
                                        className="h-11 w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500/20"
                                    />
                                    {errors.note && <p className="text-sm text-red-600">{errors.note}</p>}
                                </div>
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button type="button" variant="outline" onClick={onClose} className="border-gray-300">
                                    Cancelar
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !isFormValid}
                                    className={`ml-2 min-w-[140px] ${
                                        !isFormValid ? 'cursor-not-allowed bg-gray-400 hover:bg-gray-400' : 'bg-emerald-600 hover:bg-emerald-700'
                                    }`}
                                >
                                    {processing ? (
                                        <>
                                            <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                            Procesando...
                                        </>
                                    ) : (
                                        <>
                                            <DollarSignIcon className="mr-2 h-4 w-4" />
                                            {isEditing ? 'Actualizar pago' : 'Agregar pago'}
                                        </>
                                    )}
                                </Button>
                            </DialogFooter>

                            {wasSuccessful && (
                                <div className="mt-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                                    {isEditing ? '¡Pago actualizado exitosamente!' : '¡Pago registrado exitosamente!'}
                                </div>
                            )}
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
