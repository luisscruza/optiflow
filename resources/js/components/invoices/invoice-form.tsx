import { router } from '@inertiajs/react';
import { AlertTriangle, CreditCard, Plus, Save, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

import QuickContactModal from '@/components/contacts/quick-contact-modal';
import { EditNcfModal } from '@/components/invoices/edit-ncf-modal';
import QuickProductModal from '@/components/products/quick-product-modal';
import { TaxMultiSelect, type SelectedTax } from '@/components/taxes/tax-multi-select';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type BankAccount, type Contact, type Product, type Salesman, type TaxesGroupedByType, type Workspace } from '@/types';
import { useCurrency } from '@/utils/currency';

// Types
export interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    next_number: number;
}

export interface InvoiceItem {
    id: string;
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number;
    discount_rate: number;
    discount_amount: number;
    tax_rate: number;
    tax_amount: number;
    total: number;
    taxes: SelectedTax[];
}

export interface InvoiceFormData {
    document_subtype_id: number | null;
    ncf: string;
    contact_id: number | null;
    workspace_id: number | null;
    issue_date: string;
    due_date: string;
    payment_term: string;
    notes: string;
    items: InvoiceItem[];
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
    register_payment: boolean;
    payment_bank_account_id: number | null;
    payment_amount: number;
    payment_method: string;
    payment_notes: string;
    quotation_id: number | null;
    salesmen_ids: number[];
}

interface InvoiceFormProps {
    mode: 'create' | 'edit';
    invoiceId?: number;
    invoiceNumber?: string;
    data: InvoiceFormData;
    setData: <K extends keyof InvoiceFormData>(key: K, value: InvoiceFormData[K]) => void;
    errors: Partial<Record<keyof InvoiceFormData, string>>;
    processing: boolean;
    onSubmit: (e: React.FormEvent) => void;
    documentSubtypes: DocumentSubtype[];
    customers: Contact[];
    products: Product[];
    ncf?: string | null;
    currentWorkspace?: Workspace | null;
    availableWorkspaces?: Workspace[];
    bankAccounts?: BankAccount[];
    paymentMethods?: Record<string, string>;
    taxesGroupedByType: TaxesGroupedByType;
    salesmen: Salesman[];
}

// Helper functions
const calculateDueDate = (issueDate: string, paymentTerm: string): string => {
    if (!issueDate || paymentTerm === 'manual') return '';

    const date = new Date(issueDate);
    const daysToAdd = parseInt(paymentTerm.replace('days', ''));

    if (isNaN(daysToAdd)) return '';

    date.setDate(date.getDate() + daysToAdd);
    return date.toISOString().split('T')[0];
};

const recalculateItemTotals = (item: InvoiceItem): InvoiceItem => {
    const lineSubtotal = item.quantity * item.unit_price;
    const discountAmount = lineSubtotal * (item.discount_rate / 100);
    const discountedSubtotal = lineSubtotal - discountAmount;

    let totalTaxAmount = 0;
    let totalTaxRate = 0;
    const updatedTaxes = item.taxes.map((tax) => ({
        ...tax,
        amount: discountedSubtotal * (tax.rate / 100),
    }));
    totalTaxAmount = updatedTaxes.reduce((sum, t) => sum + t.amount, 0);
    totalTaxRate = updatedTaxes.reduce((sum, t) => sum + t.rate, 0);

    return {
        ...item,
        discount_amount: discountAmount,
        tax_rate: totalTaxRate,
        tax_amount: totalTaxAmount,
        taxes: updatedTaxes,
        total: discountedSubtotal + totalTaxAmount,
    };
};

const createEmptyItem = (id: string): InvoiceItem => ({
    id,
    product_id: null,
    description: '',
    quantity: 1,
    unit_price: 0,
    discount_rate: 0,
    discount_amount: 0,
    tax_rate: 0,
    tax_amount: 0,
    total: 0,
    taxes: [],
});

export default function InvoiceForm({
    mode,
    invoiceId,
    invoiceNumber,
    data,
    setData,
    errors,
    processing,
    onSubmit,
    documentSubtypes,
    customers,
    products,
    ncf,
    currentWorkspace,
    availableWorkspaces,
    bankAccounts = [],
    paymentMethods = {},
    taxesGroupedByType,
    salesmen,
}: InvoiceFormProps) {
    const [itemId, setItemId] = useState(() => {
        if (data.items.length > 0) {
            return Math.max(...data.items.map((item) => parseInt(item.id.toString()))) + 1;
        }
        return 1;
    });
    const [showContactModal, setShowContactModal] = useState(false);
    const [showProductModal, setShowProductModal] = useState(false);
    const [activeProductItemId, setActiveProductItemId] = useState<string | null>(null);
    const [showNcfModal, setShowNcfModal] = useState(false);
    const [contactsList, setContactsList] = useState<Contact[]>(customers);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(() => {
        if (data.contact_id) {
            return customers.find((c) => c.id === data.contact_id) || null;
        }
        return null;
    });
    const [productsList, setProductsList] = useState<Product[]>(products);

    const { format: formatCurrency } = useCurrency();

    // Sync NCF prop changes to form data
    useEffect(() => {
        if (ncf && ncf !== data.ncf) {
            setData('ncf', ncf);
        }
    }, [ncf]);

    // Calculate totals
    const calculateTotals = (items: InvoiceItem[]) => {
        const subtotal = items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);
        const discountTotal = items.reduce((sum, item) => sum + item.discount_amount, 0);
        const taxAmount = items.reduce((sum, item) => sum + item.tax_amount, 0);
        const total = subtotal - discountTotal + taxAmount;

        setData('subtotal' as keyof InvoiceFormData, subtotal as any);
        setData('discount_total' as keyof InvoiceFormData, discountTotal as any);
        setData('tax_amount' as keyof InvoiceFormData, taxAmount as any);
        setData('total' as keyof InvoiceFormData, total as any);
    };

    // Get tax breakdown for display
    const getTaxBreakdown = (): Array<{ name: string; amount: number }> => {
        const taxMap = new Map<string, number>();

        data.items.forEach((item) => {
            item.taxes.forEach((tax) => {
                const currentAmount = taxMap.get(tax.name) || 0;
                taxMap.set(tax.name, currentAmount + (Number(tax.amount) || 0));
            });
        });

        return Array.from(taxMap.entries())
            .map(([name, amount]) => ({ name, amount }))
            .sort((a, b) => b.amount - a.amount);
    };

    // Handlers
    const handleWorkspaceSwitch = (workspaceId: string) => {
        setData('workspace_id', parseInt(workspaceId));
        const url = mode === 'create' ? '/invoices/create' : `/invoices/${invoiceId}/edit`;
        router.visit(url, {
            method: 'get',
            data: { workspace_id: workspaceId },
            preserveState: false,
        });
    };

    const handleDocumentSubtypeChange = (value: string) => {
        const subtypeId = parseInt(value);
        setData('document_subtype_id', subtypeId);
        router.reload({
            only: ['ncf'],
            data: { document_subtype_id: subtypeId },
        });
    };

    const handlePaymentTermChange = (value: string) => {
        setData('payment_term', value);
        setData('due_date', calculateDueDate(data.issue_date, value));
    };

    const handleDueDateChange = (value: string) => {
        if (data.issue_date && value < data.issue_date) return;
        setData('due_date', value);
        setData('payment_term', 'manual');
    };

    const handleIssueDateChange = (value: string) => {
        setData('issue_date', value);
        if (data.payment_term !== 'manual') {
            setData('due_date', calculateDueDate(value, data.payment_term));
        }
    };

    const addItem = () => {
        const newItemId = itemId + 1;
        setItemId(newItemId);
        const newItems = [...data.items, createEmptyItem(newItemId.toString())];
        setData('items', newItems);
    };

    const removeItem = (itemId: string) => {
        if (data.items.length > 1) {
            const newItems = data.items.filter((item) => item.id !== itemId);
            setData('items', newItems);
            calculateTotals(newItems);
        }
    };

    const updateItem = (itemId: string, field: keyof InvoiceItem, value: any) => {
        const updatedItems = data.items.map((item) => {
            if (item.id === itemId) {
                const updatedItem = { ...item, [field]: value };
                if (field === 'quantity' || field === 'unit_price' || field === 'discount_rate' || field === 'taxes') {
                    return recalculateItemTotals(updatedItem);
                }
                return updatedItem;
            }
            return item;
        });
        setData('items', updatedItems);
        calculateTotals(updatedItems);
    };

    const getSelectedProduct = (item: InvoiceItem): Product | null => {
        if (!item.product_id) return null;
        return productsList.find((p) => p.id === item.product_id) || null;
    };

    const getStockWarning = (item: InvoiceItem): { hasWarning: boolean; message: string; type: 'error' | 'warning' } | null => {
        const product = getSelectedProduct(item);
        if (!product || !product.track_stock) return null;

        if (product.stock_status === 'out_of_stock') {
            return { hasWarning: true, message: `${product.name} está agotado`, type: 'error' };
        }

        if (product.stock_quantity !== undefined && item.quantity > product.stock_quantity) {
            return { hasWarning: true, message: `Stock insuficiente. Disponible: ${product.stock_quantity}`, type: 'error' };
        }

        if (product.stock_status === 'low_stock') {
            return { hasWarning: true, message: `Stock bajo. Disponible: ${product.stock_quantity}`, type: 'warning' };
        }

        return null;
    };

    const handleProductSelect = (itemId: string, productId: string) => {
        const product = productsList.find((p) => p.id === parseInt(productId));
        if (product) {
            const updatedItems = data.items.map((item) => {
                if (item.id === itemId) {
                    const taxes: SelectedTax[] = product.default_tax
                        ? [
                              {
                                  id: product.default_tax.id,
                                  name: product.default_tax.name,
                                  type: product.default_tax.type,
                                  rate: product.default_tax.rate,
                                  amount: 0,
                              },
                          ]
                        : item.taxes;

                    const updatedItem: InvoiceItem = {
                        ...item,
                        product_id: product.id,
                        description: product.name,
                        unit_price: product.price,
                        taxes,
                    };

                    return recalculateItemTotals(updatedItem);
                }
                return item;
            });

            setData('items', updatedItems);
            calculateTotals(updatedItems);
        }
    };

    const handleProductCreated = (newProduct: Product) => {
        setProductsList((prev) => [...prev, newProduct]);

        if (activeProductItemId) {
            const updatedItems = data.items.map((item) => {
                if (item.id === activeProductItemId) {
                    const taxes: SelectedTax[] = newProduct.default_tax
                        ? [
                              {
                                  id: newProduct.default_tax.id,
                                  name: newProduct.default_tax.name,
                                  type: newProduct.default_tax.type,
                                  rate: newProduct.default_tax.rate,
                                  amount: 0,
                              },
                          ]
                        : item.taxes;

                    const updatedItem: InvoiceItem = {
                        ...item,
                        product_id: newProduct.id,
                        description: newProduct.name,
                        unit_price: newProduct.price,
                        taxes,
                    };

                    return recalculateItemTotals(updatedItem);
                }
                return item;
            });

            setData('items', updatedItems);
            calculateTotals(updatedItems);
            setActiveProductItemId(null);
        }
    };

    const handleContactCreated = (newContact: Contact) => {
        setContactsList((prev) => [...prev, newContact]);
        setData('contact_id', newContact.id);
        setSelectedContact(newContact);
    };

    const handleContactSelect = (contactId: string) => {
        const contact = contactsList.find((c) => c.id === parseInt(contactId));
        setData('contact_id', parseInt(contactId));
        setSelectedContact(contact || null);
    };

    const contactOptions: SearchableSelectOption[] = contactsList.map((contact) => ({
        value: contact.id.toString(),
        label: contact.name,
    }));

    const getProductOptions = (): SearchableSelectOption[] =>
        productsList.map((product) => ({
            value: product.id.toString(),
            label: `${product.name} - ${formatCurrency(product.price)}`,
            disabled: product.track_stock && product.stock_status === 'out_of_stock',
        }));

    const isCreate = mode === 'create';
    const submitButtonText = isCreate ? 'Guardar factura' : 'Actualizar factura';
    const processingText = isCreate ? 'Guardando...' : 'Actualizando...';

    return (
        <>
            <form onSubmit={onSubmit}>
                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                    <CardContent className="px-6 py-6">
                        {/* Header */}
                        <div className="mb-8 flex items-start justify-between border-b border-gray-200 pb-6">
                            <div className="space-y-1">
                                <h1 className="text-2xl font-bold text-gray-900">Centro Óptico Visión Integral</h1>
                                <p className="text-sm text-gray-600">RNC o Cédula: 130382573</p>
                                <p className="text-sm text-gray-600">info@covi.com.do</p>
                            </div>

                            <div className="space-y-1 text-right">
                                <div className="flex items-center justify-end gap-2">
                                    <span className="text-sm font-medium text-gray-600">NCF</span>
                                    <span className="rounded bg-gray-100 px-2 py-1 font-mono text-sm">{data.ncf || 'N/A'}</span>
                                    <button
                                        type="button"
                                        onClick={() => setShowNcfModal(true)}
                                        disabled={!data.document_subtype_id}
                                        className="text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        title="Editar NCF manualmente"
                                    >
                                        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                            />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                                <div className="flex flex-col justify-end space-y-3">
                                    <Label className="gap-1 text-sm font-medium text-gray-900">
                                        Tipo de documento
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.document_subtype_id?.toString() || ''} onValueChange={handleDocumentSubtypeChange}>
                                        <SelectTrigger className={`h-8 text-xs ${errors.document_subtype_id ? 'border-red-300' : 'border-gray-300'}`}>
                                            <SelectValue placeholder="Seleccionar tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {documentSubtypes.map((subtype) => (
                                                <SelectItem key={subtype.id} value={subtype.id.toString()}>
                                                    {subtype.name} ({subtype.prefix})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.document_subtype_id && <p className="text-sm text-red-600">{errors.document_subtype_id}</p>}
                                </div>
                            </div>
                        </div>

                        {/* Customer and Document Details */}
                        <div className="grid grid-cols-2 gap-8">
                            {/* Left Column - Customer Details */}
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label className="flex items-center gap-1 text-sm font-medium text-gray-900">
                                        Contacto
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="flex gap-2">
                                        <SearchableSelect
                                            options={contactOptions}
                                            value={data.contact_id?.toString() || ''}
                                            onValueChange={handleContactSelect}
                                            placeholder="Buscar contacto..."
                                            searchPlaceholder="Escribir para buscar..."
                                            emptyText="No se encontró ningún contacto."
                                            noEmptyAction={
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setShowContactModal(true)}
                                                    className="text-primary hover:bg-primary/10"
                                                >
                                                    <Plus className="mr-1 h-4 w-4" />
                                                    Crear nuevo contacto
                                                </Button>
                                            }
                                            className="flex-1"
                                            triggerClassName={`h-10 ${errors.contact_id ? 'border-red-300 ring-red-500/20' : 'border-gray-300'}`}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setShowContactModal(true)}
                                            className="h-10 border-gray-300 px-3 text-primary hover:bg-primary/10"
                                        >
                                            <Plus className="mr-1 h-4 w-4" />
                                            Nuevo
                                        </Button>
                                    </div>
                                    {errors.contact_id && <p className="text-sm text-red-600">{errors.contact_id}</p>}
                                </div>

                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900">RNC o Cédula</Label>
                                    <Input
                                        value={selectedContact?.identification_number || ''}
                                        placeholder="RNC o número de cédula"
                                        className="h-10 border-gray-300"
                                        readOnly
                                        disabled
                                    />
                                </div>

                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900">Teléfono</Label>
                                    <Input
                                        value={selectedContact?.phone_primary || ''}
                                        placeholder="Número de teléfono"
                                        className="h-10 border-gray-300"
                                        readOnly
                                        disabled
                                    />
                                </div>

                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900">Vendedores</Label>
                                    <SearchableSelect
                                        options={salesmen.map((s) => ({ value: s.id.toString(), label: s.full_name }))}
                                        value={data.salesmen_ids.length > 0 ? data.salesmen_ids[0].toString() : ''}
                                        onValueChange={(value) => {
                                            if (value) {
                                                const salesmanId = parseInt(value);
                                                if (!data.salesmen_ids.includes(salesmanId)) {
                                                    setData('salesmen_ids', [...data.salesmen_ids, salesmanId]);
                                                }
                                            }
                                        }}
                                        placeholder="Seleccionar vendedor..."
                                        searchPlaceholder="Buscar vendedor..."
                                        emptyText="No se encontró ningún vendedor."
                                        className="flex-1"
                                        triggerClassName="h-10 border-gray-300"
                                    />
                                    {data.salesmen_ids.length > 0 && (
                                        <div className="flex flex-wrap gap-2">
                                            {data.salesmen_ids.map((salesmanId) => {
                                                const salesman = salesmen.find((s) => s.id === salesmanId);
                                                if (!salesman) return null;
                                                return (
                                                    <div
                                                        key={salesmanId}
                                                        className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-3 py-1 text-sm text-primary"
                                                    >
                                                        {salesman.full_name}
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                setData(
                                                                    'salesmen_ids',
                                                                    data.salesmen_ids.filter((id) => id !== salesmanId),
                                                                )
                                                            }
                                                            className="ml-1 text-primary hover:text-primary/80"
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Right Column - Invoice Details */}
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label className="flex items-center gap-1 text-sm font-medium text-gray-900">
                                        Fecha
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        type="date"
                                        value={data.issue_date}
                                        onChange={(e) => handleIssueDateChange(e.target.value)}
                                        className={`h-10 border-gray-300 ${errors.issue_date ? 'border-red-300' : ''}`}
                                    />
                                    {errors.issue_date && <p className="text-sm text-red-600">{errors.issue_date}</p>}
                                </div>

                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900">Plazo de pago</Label>
                                    <Select value={data.payment_term} onValueChange={handlePaymentTermChange}>
                                        <SelectTrigger className="h-10 border-gray-300">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="manual">Vencimiento manual</SelectItem>
                                            <SelectItem value="7days">7 días</SelectItem>
                                            <SelectItem value="15days">15 días</SelectItem>
                                            <SelectItem value="30days">30 días</SelectItem>
                                            <SelectItem value="45days">45 días</SelectItem>
                                            <SelectItem value="60days">60 días</SelectItem>
                                            <SelectItem value="90days">90 días</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-3">
                                    <Label className="flex items-center gap-1 text-sm font-medium text-gray-900">
                                        Vencimiento
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        type="date"
                                        value={data.due_date}
                                        min={data.issue_date}
                                        onChange={(e) => handleDueDateChange(e.target.value)}
                                        className={`h-10 border-gray-300 ${errors.due_date ? 'border-red-300' : ''}`}
                                    />
                                    {errors.due_date && <p className="text-sm text-red-600">{errors.due_date}</p>}
                                    {data.due_date && data.issue_date && data.due_date < data.issue_date && (
                                        <p className="text-sm text-red-600">La fecha de vencimiento no puede ser anterior a la fecha de emisión</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Invoice Items Section */}
                        <div className="mt-8 border-t border-gray-200 pt-8">
                            <div className="space-y-6">
                                {/* Table Header */}
                                <div className="grid grid-cols-12 gap-3 rounded-lg border bg-gray-50 px-4 py-3 text-xs font-semibold tracking-wider text-gray-700 uppercase">
                                    <div className="col-span-2">Producto</div>
                                    <div className="col-span-2">Descripción</div>
                                    <div className="col-span-1 text-center">Cant.</div>
                                    <div className="col-span-1 text-right">Precio</div>
                                    <div className="col-span-1 text-right">Desc. (%)</div>
                                    <div className="col-span-2 text-right">Impuestos</div>
                                    <div className="col-span-2 text-right">Total</div>
                                    <div className="col-span-1"></div>
                                </div>

                                {/* Items */}
                                <div className="space-y-2">
                                    {data.items.map((item) => (
                                        <div
                                            key={item.id}
                                            className="grid grid-cols-12 items-center gap-3 border-b border-gray-100 py-3 last:border-b-0"
                                        >
                                            {/* Product Selection */}
                                            <div className="col-span-2">
                                                <SearchableSelect
                                                    options={getProductOptions()}
                                                    value={item.product_id?.toString() || ''}
                                                    onValueChange={(value) => handleProductSelect(item.id, value)}
                                                    placeholder="Seleccionar..."
                                                    searchPlaceholder="Buscar producto..."
                                                    emptyText="No se encontró ningún producto."
                                                    footerAction={
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                setActiveProductItemId(item.id);
                                                                setShowProductModal(true);
                                                            }}
                                                            className="w-full text-primary hover:bg-primary/10"
                                                        >
                                                            <Plus className="mr-1 h-4 w-4" />
                                                            Crear producto
                                                        </Button>
                                                    }
                                                    triggerClassName="h-9 border-gray-200"
                                                />
                                            </div>

                                            {/* Description */}
                                            <div className="col-span-2">
                                                <Input
                                                    placeholder="Descripción..."
                                                    value={item.description}
                                                    onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                    className="h-9 border-gray-200"
                                                    disabled={!item.product_id}
                                                />
                                            </div>

                                            {/* Quantity */}
                                            <div className="relative col-span-1">
                                                <Input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                    className={`h-9 border-gray-200 text-center ${(() => {
                                                        const warning = getStockWarning(item);
                                                        if (warning?.type === 'error') return 'border-red-300';
                                                        if (warning?.type === 'warning') return 'border-yellow-300';
                                                        return '';
                                                    })()}`}
                                                    disabled={!item.product_id}
                                                />
                                                {(() => {
                                                    const warning = getStockWarning(item);
                                                    if (warning) {
                                                        return (
                                                            <div className="group absolute -top-1 -right-1">
                                                                <AlertTriangle
                                                                    className={`h-4 w-4 cursor-help ${warning.type === 'error' ? 'text-red-500' : 'text-yellow-500'}`}
                                                                />
                                                                <div className="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform rounded bg-gray-900 px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                                                    {warning.message}
                                                                </div>
                                                            </div>
                                                        );
                                                    }
                                                    return null;
                                                })()}
                                            </div>

                                            {/* Unit Price */}
                                            <div className="col-span-1">
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={item.unit_price}
                                                    onChange={(e) => updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)}
                                                    className="h-9 border-gray-200 text-right"
                                                    disabled={!item.product_id}
                                                />
                                            </div>

                                            {/* Discount Rate */}
                                            <div className="group relative col-span-1">
                                                <Input
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    value={item.discount_rate}
                                                    onChange={(e) => updateItem(item.id, 'discount_rate', parseFloat(e.target.value) || 0)}
                                                    className="h-9 border-gray-200 text-right"
                                                    disabled={!item.product_id}
                                                />
                                                {item.product_id && (
                                                    <div className="absolute -bottom-7 left-1/2 z-10 hidden -translate-x-1/2 gap-1 rounded-md bg-white p-1 shadow-lg ring-1 ring-gray-200 group-hover:flex">
                                                        {[5, 10, 15, 20].map((discount) => (
                                                            <button
                                                                key={discount}
                                                                type="button"
                                                                onClick={() => updateItem(item.id, 'discount_rate', discount)}
                                                                className={`rounded px-2 py-0.5 text-xs font-medium transition-colors ${item.discount_rate === discount ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-primary/10 hover:text-primary'}`}
                                                            >
                                                                {discount}%
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>

                                            {/* Tax Multi-Select */}
                                            <div className="col-span-2">
                                                <TaxMultiSelect
                                                    taxesGroupedByType={taxesGroupedByType}
                                                    selectedTaxes={item.taxes}
                                                    onSelectionChange={(taxes) => updateItem(item.id, 'taxes', taxes)}
                                                    taxableAmount={item.quantity * item.unit_price - item.discount_amount}
                                                    disabled={!item.product_id}
                                                    placeholder="—"
                                                    className="h-9"
                                                />
                                            </div>

                                            {/* Total */}
                                            <div className="col-span-2">
                                                <Input
                                                    value={formatCurrency(item.total)}
                                                    disabled
                                                    className="h-9 border-gray-200 bg-gray-50 text-right font-semibold text-gray-900"
                                                />
                                            </div>

                                            {/* Remove Button */}
                                            <div className="col-span-1 flex justify-end">
                                                {data.items.length > 1 && (
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => removeItem(item.id)}
                                                        className="h-8 w-8 p-0 text-red-500 hover:bg-red-50 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Add Item Button */}
                                <div className="flex items-center justify-between">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addItem}
                                        className="flex items-center gap-2 border-primary bg-background text-primary hover:border-primary hover:bg-primary/10"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Agregar línea
                                    </Button>
                                </div>

                                {/* Totals */}
                                <div className="border-t border-gray-200 pt-6">
                                    <div className="flex justify-end">
                                        <div className="w-full max-w-sm space-y-3">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-gray-600">Subtotal:</span>
                                                <span className="font-medium text-gray-900">{formatCurrency(data.subtotal)}</span>
                                            </div>
                                            {data.discount_total > 0 && (
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-600">Descuentos:</span>
                                                    <span className="font-medium text-red-600">-{formatCurrency(data.discount_total)}</span>
                                                </div>
                                            )}
                                            {getTaxBreakdown().length > 0 && (
                                                <div className="space-y-2 border-t border-gray-100 pt-2">
                                                    {getTaxBreakdown().map((tax) => (
                                                        <div key={tax.name} className="flex items-center justify-between text-sm">
                                                            <span className="text-gray-600">{tax.name}:</span>
                                                            <span className="font-medium text-gray-900">+{formatCurrency(tax.amount)}</span>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                            <div className="flex items-center justify-between border-t border-gray-200 pt-3 text-lg font-bold">
                                                <span className="text-gray-900">Total:</span>
                                                <span className="text-primary">{formatCurrency(data.total)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Immediate Payment Section (Create only) */}
                        {isCreate && bankAccounts.length > 0 && (
                            <div className="mt-8 border-t border-gray-200 pt-8">
                                <div className="mb-6 flex items-center justify-between">
                                    <div>
                                        <h3 className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600">
                                                <CreditCard className="h-4 w-4" />
                                            </div>
                                            Registrar pago inmediato
                                        </h3>
                                        <p className="mt-1 text-sm text-gray-600">
                                            Si el cliente paga de inmediato, registra el pago junto con la factura.
                                        </p>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="register_payment"
                                            checked={data.register_payment}
                                            onCheckedChange={(checked) => {
                                                setData('register_payment', checked === true);
                                                if (checked === true) {
                                                    setData('payment_amount', data.total);
                                                } else {
                                                    setData('payment_amount', 0);
                                                }
                                            }}
                                        />
                                        <Label htmlFor="register_payment" className="cursor-pointer text-sm font-medium text-gray-700">
                                            Registrar pago
                                        </Label>
                                    </div>
                                </div>

                                {data.register_payment && (
                                    <div className="space-y-6">
                                        <div className="rounded-lg border-2 border-yellow-200 bg-gradient-to-br from-yellow-50 to-yellow-100/50 p-5 shadow-sm">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="text-xs font-medium tracking-wide text-yellow-700 uppercase">Total de la factura</p>
                                                    <p className="text-2xl font-bold text-yellow-600">{formatCurrency(data.total)}</p>
                                                </div>
                                                {data.payment_amount > 0 && data.payment_amount < data.total && (
                                                    <div className="text-right">
                                                        <p className="text-xs font-medium tracking-wide text-gray-600 uppercase">
                                                            Pendiente después del pago
                                                        </p>
                                                        <p className="text-lg font-bold text-gray-700">
                                                            {formatCurrency(data.total - data.payment_amount)}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-6">
                                            <div className="space-y-2">
                                                <Label className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                                    Cuenta bancaria
                                                    <span className="text-red-500">*</span>
                                                </Label>
                                                <Select
                                                    value={data.payment_bank_account_id?.toString() || ''}
                                                    onValueChange={(value) => setData('payment_bank_account_id', parseInt(value))}
                                                >
                                                    <SelectTrigger
                                                        className={`h-11 ${data.payment_bank_account_id ? 'border-yellow-300 bg-yellow-50/30' : 'border-gray-300'}`}
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
                                            </div>

                                            <div className="space-y-2">
                                                <Label className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                                    Método de pago
                                                    <span className="text-red-500">*</span>
                                                </Label>
                                                <Select value={data.payment_method} onValueChange={(value) => setData('payment_method', value)}>
                                                    <SelectTrigger
                                                        className={`h-11 ${data.payment_method ? 'border-yellow-300 bg-yellow-50/30' : 'border-gray-300'}`}
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
                                            </div>

                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between">
                                                    <Label className="flex items-center gap-1 text-sm font-semibold text-gray-900">
                                                        Monto del pago
                                                        <span className="text-red-500">*</span>
                                                    </Label>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => setData('payment_amount', data.total)}
                                                        className="h-7 border-yellow-300 px-3 py-1 text-xs text-yellow-700 hover:bg-yellow-50"
                                                    >
                                                        Monto completo
                                                    </Button>
                                                </div>
                                                <div className="relative">
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        max={data.total}
                                                        value={data.payment_amount || ''}
                                                        onChange={(e) => setData('payment_amount', parseFloat(e.target.value) || 0)}
                                                        placeholder="0.00"
                                                        className={`h-12 pl-14 text-lg font-semibold ${data.payment_amount > 0 ? 'border-yellow-300 bg-yellow-50/30 text-yellow-700' : 'border-gray-300'}`}
                                                    />
                                                    <span className="absolute top-1/2 left-4 -translate-y-1/2 text-lg font-semibold text-gray-500">
                                                        RD$
                                                    </span>
                                                </div>
                                                {data.payment_amount > data.total && (
                                                    <p className="text-sm text-amber-600">⚠️ El monto excede el total de la factura</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label className="text-sm font-semibold text-gray-900">Nota del pago (opcional)</Label>
                                                <Input
                                                    value={data.payment_notes}
                                                    onChange={(e) => setData('payment_notes', e.target.value)}
                                                    placeholder="Información adicional sobre el pago"
                                                    className="h-11 border-gray-300"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Notes Section */}
                        <div className="mt-8 border-t border-gray-200 pt-8">
                            <div className="mb-6">
                                <h3 className="flex items-center gap-3 text-lg font-semibold text-gray-900">Notas adicionales</h3>
                                <p className="mt-1 text-sm text-gray-600">Información adicional que aparecerá en la factura (opcional).</p>
                            </div>

                            <div className="space-y-3">
                                <Label htmlFor="notes" className="text-sm font-medium text-gray-900">
                                    Notas o comentarios
                                </Label>
                                <Textarea
                                    id="notes"
                                    placeholder="Ej: Términos de pago, instrucciones especiales, agradecimientos..."
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={4}
                                    className="resize-none border-gray-200"
                                />
                                <p className="text-xs text-gray-500">Estas notas aparecerán al final de la factura</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="mt-8 flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        asChild
                        className="border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50"
                    >
                        <a href="/invoices" className="flex items-center justify-center gap-2">
                            Cancelar
                        </a>
                    </Button>
                    <Button
                        type="submit"
                        size="lg"
                        disabled={processing || !ncf || !data.contact_id || data.items.length === 0}
                        className={`flex min-w-[160px] items-center justify-center gap-2 ${processing || !ncf ? 'bg-gray-400 hover:bg-gray-400' : 'bg-primary hover:bg-primary/90'}`}
                    >
                        {processing ? (
                            <>
                                <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                {processingText}
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4" />
                                {submitButtonText}
                            </>
                        )}
                    </Button>
                </div>
            </form>

            <QuickContactModal
                open={showContactModal}
                onOpenChange={setShowContactModal}
                onSuccess={handleContactCreated}
                onAdvancedForm={() => router.visit('/contacts/create')}
            />

            <EditNcfModal
                isOpen={showNcfModal}
                onClose={() => setShowNcfModal(false)}
                currentNcf={data.ncf}
                prefix={documentSubtypes.find((d) => d.id === data.document_subtype_id)?.prefix || ''}
                nextNumber={documentSubtypes.find((d) => d.id === data.document_subtype_id)?.next_number || 0}
                onSave={(newNcf) => setData('ncf', newNcf)}
                invoiceId={invoiceId}
            />

            <QuickProductModal
                open={showProductModal}
                onOpenChange={setShowProductModal}
                onSuccess={handleProductCreated}
                onAdvancedForm={() => router.visit('/products/create')}
            />
        </>
    );
}
