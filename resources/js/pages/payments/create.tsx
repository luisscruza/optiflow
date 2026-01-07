import { Form, Link } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import {
    type BankAccount,
    type BreadcrumbItem,
    type ChartAccount,
    type Contact,
    type Invoice,
    type PaymentConcept,
    type Tax,
    type WithholdingType,
} from '@/types';
import { useCurrency } from '@/utils/currency';

interface PaymentLine {
    id?: number;
    payment_concept_id?: number;
    chart_account_id: number;
    description: string;
    quantity: number;
    unit_price: number;
    tax_id?: number;
}

interface PaymentWithholding {
    id?: number;
    withholding_type_id: number;
    base_amount: number;
}

interface Props {
    pendingInvoices: Array<Invoice & { amount_due: number }>;
    contacts: Contact[];
    bankAccounts: BankAccount[];
    paymentMethods: Record<string, string>;
    paymentTypes: Record<string, string>;
    chartAccounts: ChartAccount[];
    paymentConcepts: PaymentConcept[];
    withholdingTypes: WithholdingType[];
    taxes: Tax[];
}

export default function PaymentCreate({
    pendingInvoices,
    contacts,
    bankAccounts,
    paymentMethods,
    paymentTypes,
    chartAccounts,
    paymentConcepts,
    withholdingTypes,
    taxes,
}: Props) {
    const { format: formatCurrency } = useCurrency();
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pagos recibidos', href: '/payments' },
        { title: 'Registrar pago', href: '/payments/create' },
    ];

    const [paymentType, setPaymentType] = useState('other_income');
    const [selectedInvoice, setSelectedInvoice] = useState('');
    const [selectedContact, setSelectedContact] = useState('');
    const [selectedBankAccount, setSelectedBankAccount] = useState('');
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState('');
    const [paymentDate, setPaymentDate] = useState(new Date().toISOString().split('T')[0]);
    const [note, setNote] = useState('');

    // Lines state
    const [lines, setLines] = useState<PaymentLine[]>([{ chart_account_id: 0, description: '', quantity: 1, unit_price: 0 }]);
    const [withholdings, setWithholdings] = useState<PaymentWithholding[]>([]);

    // Group chart accounts by type
    const groupedAccounts = useMemo(() => {
        const groups: Record<string, ChartAccount[]> = {};
        chartAccounts.forEach((account) => {
            if (!groups[account.type]) {
                groups[account.type] = [];
            }
            groups[account.type].push(account);
        });
        return groups;
    }, [chartAccounts]);

    const accountTypeLabels: Record<string, string> = {
        income: 'Ingresos',
        asset: 'Activos',
        liability: 'Pasivos',
        equity: 'Patrimonio',
        expense: 'Gastos',
    };

    // Calculate totals
    const subtotal = lines.reduce((sum, line) => sum + line.quantity * line.unit_price, 0);
    const taxTotal = lines.reduce((sum, line) => {
        if (!line.tax_id) return sum;
        const tax = taxes.find((t) => t.id === line.tax_id);
        return sum + (tax ? (line.quantity * line.unit_price * tax.rate) / 100 : 0);
    }, 0);
    const withholdingTotal = withholdings.reduce((sum, w) => {
        const withholdingType = withholdingTypes.find((wt) => wt.id === w.withholding_type_id);
        return sum + (withholdingType ? (w.base_amount * withholdingType.percentage) / 100 : 0);
    }, 0);
    const total = subtotal + taxTotal - withholdingTotal;

    const handleAddLine = () => {
        setLines([...lines, { chart_account_id: 0, description: '', quantity: 1, unit_price: 0 }]);
    };

    const handleRemoveLine = (index: number) => {
        setLines(lines.filter((_, i) => i !== index));
    };

    const handleLineChange = (index: number, field: keyof PaymentLine, value: any) => {
        const newLines = [...lines];
        newLines[index] = { ...newLines[index], [field]: value };
        setLines(newLines);
    };

    const handleAddWithholding = () => {
        setWithholdings([...withholdings, { withholding_type_id: 0, base_amount: subtotal }]);
    };

    const handleRemoveWithholding = (index: number) => {
        setWithholdings(withholdings.filter((_, i) => i !== index));
    };

    const handleWithholdingChange = (index: number, field: keyof PaymentWithholding, value: any) => {
        const newWithholdings = [...withholdings];
        newWithholdings[index] = { ...newWithholdings[index], [field]: value };
        setWithholdings(newWithholdings);
    };

    const selectedInvoiceData = pendingInvoices.find((inv) => inv.id.toString() === selectedInvoice);
    const isFormValid =
        selectedBankAccount &&
        selectedPaymentMethod &&
        paymentDate &&
        (paymentType === 'invoice_payment'
            ? selectedInvoice
            : lines.every((line) => line.chart_account_id && line.description && line.quantity > 0 && line.unit_price >= 0));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
<div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/payments">
                            <ArrowLeft className="mr-2 size-4" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">Registrar pago</h1>
                        <p className="text-sm text-muted-foreground">Crea un nuevo registro de pago recibido</p>
                    </div>
                </div>

                <Form action="/payments" method="post" className="space-y-4">
                    {/* Payment Type Tabs */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Tipo de pago</CardTitle>
                            <CardDescription>Selecciona si es un pago de factura u otros ingresos</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Tabs value={paymentType} onValueChange={setPaymentType}>
                                <TabsList className="grid w-full grid-cols-2">
                                    <TabsTrigger value="invoice_payment">Pago de Factura</TabsTrigger>
                                    <TabsTrigger value="other_income">Otros Ingresos</TabsTrigger>
                                </TabsList>

                                {/* Invoice Payment */}
                                <TabsContent value="invoice_payment" className="mt-4 space-y-4">
                                    <input type="hidden" name="payment_type" value="invoice_payment" />

                                    <div className="space-y-2">
                                        <Label htmlFor="invoice_id">Factura *</Label>
                                        <Select value={selectedInvoice} onValueChange={setSelectedInvoice} name="invoice_id" required>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona una factura pendiente" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {pendingInvoices.map((invoice) => (
                                                    <SelectItem key={invoice.id} value={invoice.id.toString()}>
                                                        {invoice.document_number} - {invoice.contact.name} ({formatCurrency(invoice.amount_due)})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {selectedInvoiceData && (
                                        <Card className="border-blue-200 bg-blue-50">
                                            <CardContent className="pt-4">
                                                <div className="grid gap-4 md:grid-cols-3">
                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Cliente</p>
                                                        <p className="font-semibold">{selectedInvoiceData.contact.name}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Total Factura</p>
                                                        <p className="font-semibold">{formatCurrency(selectedInvoiceData.total_amount)}</p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm font-medium text-muted-foreground">Por Cobrar</p>
                                                        <p className="font-semibold text-blue-600">
                                                            {formatCurrency(selectedInvoiceData.amount_due)}
                                                        </p>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    <div className="space-y-2">
                                        <Label htmlFor="amount">Monto delpago *</Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            name="amount"
                                            step="0.01"
                                            min="0"
                                            max={selectedInvoiceData?.amount_due}
                                            required={paymentType === 'invoice_payment'}
                                            placeholder="0.00"
                                        />
                                    </div>
                                </TabsContent>

                                {/* Other Income */}
                                <TabsContent value="other_income" className="mt-4 space-y-4">
                                    <input type="hidden" name="payment_type" value="other_income" />

                                    <div className="space-y-2">
                                        <Label htmlFor="contact_id">Contacto (Opcional)</Label>
                                        <Select value={selectedContact} onValueChange={setSelectedContact} name="contact_id">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un contacto (opcional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {contacts.map((contact) => (
                                                    <SelectItem key={contact.id} value={contact.id.toString()}>
                                                        {contact.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Lines Section */}
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-base font-semibold">Líneas de Detalle *</Label>
                                            <Button type="button" size="sm" variant="outline" onClick={handleAddLine}>
                                                <Plus className="mr-1 size-3" />
                                                Agregar
                                            </Button>
                                        </div>

                                        <div className="space-y-2">
                                            {lines.map((line, index) => (
                                                <Card key={index} className="p-3">
                                                    <div className="space-y-3">
                                                        {/* First Row: Concept and Account */}
                                                        <div className="grid gap-3 md:grid-cols-2">
                                                            <div className="space-y-1.5">
                                                                <Label className="text-xs">Concepto</Label>
                                                                <Select
                                                                    value={line.payment_concept_id?.toString() || ''}
                                                                    onValueChange={(value) => {
                                                                        handleLineChange(
                                                                            index,
                                                                            'payment_concept_id',
                                                                            value ? parseInt(value) : undefined,
                                                                        );
                                                                        // Auto-fill account and description from concept
                                                                        const concept = paymentConcepts.find((c) => c.id === parseInt(value));
                                                                        if (concept) {
                                                                            handleLineChange(index, 'chart_account_id', concept.chart_account_id);
                                                                            if (!line.description) {
                                                                                handleLineChange(index, 'description', concept.name);
                                                                            }
                                                                        }
                                                                    }}
                                                                    name={`lines.${index}.payment_concept_id`}
                                                                >
                                                                    <SelectTrigger className="h-9">
                                                                        <SelectValue placeholder="Seleccionar..." />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {paymentConcepts.map((concept) => (
                                                                            <SelectItem key={concept.id} value={concept.id.toString()}>
                                                                                <span className="mr-2 font-mono text-xs text-muted-foreground">
                                                                                    {concept.code}
                                                                                </span>
                                                                                {concept.name}
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>

                                                            <div className="space-y-1.5">
                                                                <Label className="text-xs">Cuenta Contable *</Label>
                                                                <Select
                                                                    value={line.chart_account_id?.toString() || ''}
                                                                    onValueChange={(value) =>
                                                                        handleLineChange(index, 'chart_account_id', parseInt(value))
                                                                    }
                                                                    name={`lines.${index}.chart_account_id`}
                                                                    required
                                                                >
                                                                    <SelectTrigger className="h-9">
                                                                        <SelectValue placeholder="Seleccionar..." />
                                                                    </SelectTrigger>
                                                                    <SelectContent className="max-h-[300px]">
                                                                        {Object.entries(groupedAccounts).map(([type, accounts]) => (
                                                                            <SelectGroup key={type}>
                                                                                <SelectLabel className="text-xs font-semibold text-primary">
                                                                                    {accountTypeLabels[type] || type}
                                                                                </SelectLabel>
                                                                                {accounts.map((account) => (
                                                                                    <SelectItem
                                                                                        key={account.id}
                                                                                        value={account.id.toString()}
                                                                                        className="pl-6"
                                                                                    >
                                                                                        <span className="mr-2 font-mono text-xs text-muted-foreground">
                                                                                            {account.code}
                                                                                        </span>
                                                                                        <span className="text-sm">{account.name}</span>
                                                                                    </SelectItem>
                                                                                ))}
                                                                            </SelectGroup>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                        </div>

                                                        {/* Second Row: Description */}
                                                        <div className="space-y-1.5">
                                                            <Label className="text-xs">Descripción *</Label>
                                                            <Input
                                                                value={line.description}
                                                                onChange={(e) => handleLineChange(index, 'description', e.target.value)}
                                                                name={`lines.${index}.description`}
                                                                placeholder="Descripción del ingreso"
                                                                className="h-9"
                                                                required
                                                            />
                                                        </div>

                                                        {/* Third Row: Amounts */}
                                                        <div className="grid grid-cols-5 gap-2">
                                                            <div className="space-y-1.5">
                                                                <Label className="text-xs">Cant.</Label>
                                                                <Input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    value={line.quantity}
                                                                    onChange={(e) =>
                                                                        handleLineChange(index, 'quantity', parseFloat(e.target.value) || 0)
                                                                    }
                                                                    name={`lines.${index}.quantity`}
                                                                    className="h-9"
                                                                    required
                                                                />
                                                            </div>
                                                            <div className="col-span-2 space-y-1.5">
                                                                <Label className="text-xs">Precio Unit.</Label>
                                                                <Input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    value={line.unit_price}
                                                                    onChange={(e) =>
                                                                        handleLineChange(index, 'unit_price', parseFloat(e.target.value) || 0)
                                                                    }
                                                                    name={`lines.${index}.unit_price`}
                                                                    className="h-9"
                                                                    required
                                                                />
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label className="text-xs">Impuesto</Label>
                                                                <Select
                                                                    value={line.tax_id?.toString() || ''}
                                                                    onValueChange={(value) =>
                                                                        handleLineChange(index, 'tax_id', value ? parseInt(value) : undefined)
                                                                    }
                                                                    name={`lines.${index}.tax_id`}
                                                                >
                                                                    <SelectTrigger className="h-9">
                                                                        <SelectValue placeholder="N/A" />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {taxes.map((tax) => (
                                                                            <SelectItem key={tax.id} value={tax.id.toString()}>
                                                                                {tax.rate}%
                                                                            </SelectItem>
                                                                        ))}
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                            <div className="space-y-1.5">
                                                                <Label className="text-xs">Subtotal</Label>
                                                                <Input
                                                                    disabled
                                                                    value={formatCurrency(line.quantity * line.unit_price)}
                                                                    className="h-9 bg-muted"
                                                                />
                                                            </div>
                                                        </div>

                                                        {lines.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                className="h-7 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                                onClick={() => handleRemoveLine(index)}
                                                            >
                                                                <Trash2 className="mr-1 size-3" />
                                                                Eliminar
                                                            </Button>
                                                        )}
                                                    </div>
                                                </Card>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Withholdings Section */}
                                    {withholdings.length > 0 && (
                                        <div className="space-y-2 border-t pt-2">
                                            <Label className="text-sm font-semibold">Retenciones</Label>
                                            {withholdings.map((withholding, index) => (
                                                <Card key={index} className="p-3">
                                                    <div className="grid gap-3 md:grid-cols-3">
                                                        <div className="space-y-1.5 md:col-span-2">
                                                            <Label className="text-xs">Tipo de Retención *</Label>
                                                            <Select
                                                                value={withholding.withholding_type_id?.toString() || ''}
                                                                onValueChange={(value) =>
                                                                    handleWithholdingChange(index, 'withholding_type_id', parseInt(value))
                                                                }
                                                                name={`withholdings.${index}.withholding_type_id`}
                                                                required
                                                            >
                                                                <SelectTrigger className="h-9">
                                                                    <SelectValue placeholder="Seleccionar..." />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {withholdingTypes.map((wt) => (
                                                                        <SelectItem key={wt.id} value={wt.id.toString()}>
                                                                            {wt.name} ({wt.percentage}%)
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                        <div className="space-y-1.5">
                                                            <Label className="text-xs">Base Imponible *</Label>
                                                            <div className="flex gap-1">
                                                                <Input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    value={withholding.base_amount}
                                                                    onChange={(e) =>
                                                                        handleWithholdingChange(index, 'base_amount', parseFloat(e.target.value) || 0)
                                                                    }
                                                                    name={`withholdings.${index}.base_amount`}
                                                                    className="h-9"
                                                                    required
                                                                />
                                                                <Button
                                                                    type="button"
                                                                    size="icon"
                                                                    variant="ghost"
                                                                    className="h-9 w-9 text-destructive hover:text-destructive"
                                                                    onClick={() => handleRemoveWithholding(index)}
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </Card>
                                            ))}
                                        </div>
                                    )}

                                    {/* Add Withholding Button */}
                                    <Button type="button" size="sm" variant="outline" onClick={handleAddWithholding} className="w-full border-dashed">
                                        <Plus className="mr-1 size-3" />
                                        Agregar Retención
                                    </Button>
                                </TabsContent>
                            </Tabs>
                        </CardContent>
                    </Card>

                    {/* Common Fields */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Datos delpago</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="grid gap-3 md:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="bank_account_id" className="text-xs">
                                        Cuenta Bancaria *
                                    </Label>
                                    <Select value={selectedBankAccount} onValueChange={setSelectedBankAccount} name="bank_account_id" required>
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder="Seleccionar..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {bankAccounts.map((account) => (
                                                <SelectItem key={account.id} value={account.id.toString()}>
                                                    {account.name}
                                                    <span className="ml-2 text-xs text-muted-foreground">({account.currency?.code})</span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="payment_method" className="text-xs">
                                        Método de pago *
                                    </Label>
                                    <Select value={selectedPaymentMethod} onValueChange={setSelectedPaymentMethod} name="payment_method" required>
                                        <SelectTrigger className="h-9">
                                            <SelectValue placeholder="Seleccionar..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(paymentMethods).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="payment_date" className="text-xs">
                                        Fecha *
                                    </Label>
                                    <Input
                                        id="payment_date"
                                        type="date"
                                        name="payment_date"
                                        value={paymentDate}
                                        onChange={(e) => setPaymentDate(e.target.value)}
                                        className="h-9"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="note" className="text-xs">
                                    Nota (Opcional)
                                </Label>
                                <Textarea
                                    id="note"
                                    name="note"
                                    value={note}
                                    onChange={(e) => setNote(e.target.value)}
                                    placeholder="Información adicional..."
                                    rows={2}
                                    className="resize-none text-sm"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Totals Summary - Compact */}
                    {paymentType === 'other_income' && (
                        <Card className="border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100/50">
                            <CardContent className="pt-4">
                                <div className="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Subtotal:</span>
                                        <span className="font-medium">{formatCurrency(subtotal)}</span>
                                    </div>
                                    {taxTotal > 0 && (
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Impuestos:</span>
                                            <span className="font-medium">{formatCurrency(taxTotal)}</span>
                                        </div>
                                    )}
                                    {withholdingTotal > 0 && (
                                        <div className="col-span-2 flex justify-between text-red-600">
                                            <span>Retenciones:</span>
                                            <span className="font-medium">-{formatCurrency(withholdingTotal)}</span>
                                        </div>
                                    )}
                                    <div className="col-span-2 mt-1 flex justify-between border-t border-blue-300 pt-2 text-lg font-bold text-blue-900">
                                        <span>Total:</span>
                                        <span>{formatCurrency(total)}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Submit Button */}
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/payments">Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={!isFormValid}>
                            Registrar pago
                        </Button>
                    </div>
                </Form>
            </div>
        </AppLayout>
    );
}
