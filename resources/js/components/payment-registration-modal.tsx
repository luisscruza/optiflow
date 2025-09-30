import { Form } from '@inertiajs/react';
import { CalendarIcon, DollarSignIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BankAccount, type Invoice } from '@/types';
import { useCurrency } from '@/utils/currency';

interface PaymentRegistrationModalProps {
    isOpen: boolean;
    onClose: () => void;
    invoice: Invoice;
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
}

export function PaymentRegistrationModal({ 
    isOpen, 
    onClose, 
    invoice, 
    bankAccounts, 
    paymentMethods 
}: PaymentRegistrationModalProps) {
    const [selectedBankAccount, setSelectedBankAccount] = useState<string>('');
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<string>('');
    const [amount, setAmount] = useState<string>('');
    const { format: formatCurrency } = useCurrency();

    // Format today's datetime for the input default value (datetime-local format)
    const now = new Date();
    const today = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

    const handleFillFullAmount = () => {
        setAmount(invoice.amount_due.toString());
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center space-x-2">
                        <DollarSignIcon className="h-5 w-5" />
                        <span>Registrar pago</span>
                    </DialogTitle>
                    <DialogDescription>
                        Registra un pago para la factura {invoice.document_number}
                        <br />
                        <strong>Cliente:</strong> {invoice.contact.name}
                        <br />
                        <strong>Valor por cobrar:</strong> {formatCurrency(invoice.amount_due)}
                    </DialogDescription>
                </DialogHeader>

                <Form action="/payments" method="post" resetOnSuccess onSuccess={onClose}>
                    {({ processing, wasSuccessful, errors }) => (
                        <div className="space-y-4">
                            <input type="hidden" name="invoice_id" value={invoice.id} />
                            
                            <div className="space-y-4 py-4">
                                {/* Payment Date */}
                                <div className="space-y-2">
                                    <Label htmlFor="payment_date" className="text-right">
                                        Fecha y hora
                                    </Label>
                                    <div className="relative">
                                        <Input
                                            id="payment_date"
                                            name="payment_date"
                                            type="datetime-local"
                                            defaultValue={today}
                                            className="w-full"
                                            required
                                        />
                                        <CalendarIcon className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none" />
                                    </div>
                                    {errors.payment_date && (
                                        <p className="text-sm text-red-600">{errors.payment_date}</p>
                                    )}
                                </div>

                                {/* Bank Account */}
                                <div className="space-y-2">
                                    <Label htmlFor="bank_account_id">Cuenta bancaria</Label>
                                    <Select name="bank_account_id" value={selectedBankAccount} onValueChange={setSelectedBankAccount} required>
                                        <SelectTrigger>
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
                                    {errors.bank_account_id && (
                                        <p className="text-sm text-red-600">{errors.bank_account_id}</p>
                                    )}
                                </div>

                                {/* Amount */}
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="amount">Valor</Label>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleFillFullAmount}
                                            className="text-xs px-2 py-1 h-6"
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
                                            min="0"
                                            max={invoice.amount_due}
                                            value={amount}
                                            onChange={(e) => setAmount(e.target.value)}
                                            placeholder="0.00"
                                            className="pl-12"
                                            required
                                        />
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                                            RD$
                                        </span>
                                    </div>
                                    {errors.amount && (
                                        <p className="text-sm text-red-600">{errors.amount}</p>
                                    )}
                                </div>

                                {/* Payment Method */}
                                <div className="space-y-2">
                                    <Label htmlFor="payment_method">Método de pago</Label>
                                    <Select name="payment_method" value={selectedPaymentMethod} onValueChange={setSelectedPaymentMethod} required>
                                        <SelectTrigger>
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
                                    {errors.payment_method && (
                                        <p className="text-sm text-red-600">{errors.payment_method}</p>
                                    )}
                                </div>

                                {/* Note */}
                                <div className="space-y-2">
                                    <Label htmlFor="note">Nota (opcional)</Label>
                                    <Input
                                        id="note"
                                        name="note"
                                        placeholder="Información adicional sobre el pago"
                                        className="w-full"
                                    />
                                    {errors.note && (
                                        <p className="text-sm text-red-600">{errors.note}</p>
                                    )}
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={onClose}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing} className="bg-primary hover:bg-primary/90">
                                    {processing ? 'Procesando...' : 'Agregar pago'}
                                </Button>
                            </DialogFooter>

                            {wasSuccessful && (
                                <div className="mt-2 p-3 bg-green-50 text-green-700 rounded-md text-sm">
                                    ¡Pago registrado exitosamente!
                                </div>
                            )}
                        </div>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}