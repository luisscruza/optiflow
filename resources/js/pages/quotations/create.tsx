import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Calendar, FileText, Plus, Save, ShoppingCart, User, Receipt, Hash, Trash2, AlertTriangle, Building2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import QuickContactModal from '@/components/contacts/quick-contact-modal';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Product, type Workspace } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cotizaciones',
        href: '/quotations',
    },
    {
        title: 'Nueva cotización',
        href: '/quotations/create',
    },
];

interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    next_number: number;
}

interface QuotationItem {
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
}

interface FormData {
    document_subtype_id: number | null;
    ncf: string;
    contact_id: number | null;
    workspace_id: number | null;
    issue_date: string;
    due_date: string;
    payment_term: string;
    notes: string;
    items: QuotationItem[];
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
}

interface Props {
    documentSubtypes: DocumentSubtype[];
    customers: Contact[];
    products: Product[];
    ncf?: string | null;
    document_subtype_id?: number | null;
    currentWorkspace?: Workspace | null;
    availableWorkspaces?: Workspace[];
}

export default function CreateQuotation({ documentSubtypes, customers, products, ncf, document_subtype_id, currentWorkspace, availableWorkspaces }: Props) {
    const [itemId, setItemId] = useState(3);
    const [showContactModal, setShowContactModal] = useState(false);
    const [contactsList, setContactsList] = useState<Contact[]>(customers);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(null);

    const { format: formatCurrency } = useCurrency();

    const calculateDueDate = (issueDate: string, paymentTerm: string): string => {
        if (!issueDate || paymentTerm === 'manual') return '';

        const date = new Date(issueDate);
        const daysToAdd = parseInt(paymentTerm.replace('days', ''));

        if (isNaN(daysToAdd)) return '';

        date.setDate(date.getDate() + daysToAdd);
        return date.toISOString().split('T')[0];
    };

    const { data, setData, post, processing, errors } = useForm<FormData>({
        document_subtype_id: document_subtype_id || null,
        contact_id: null,
        workspace_id: currentWorkspace?.id || null,
        issue_date: new Date().toISOString().split('T')[0],
            due_date: new Date(new Date().setDate(new Date().getDate() + 15)).toISOString().split('T')[0],
        payment_term: '15days',
        notes: '',
        ncf: ncf || '',
        items: [
            {
                id: '1',
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_rate: 0,
                discount_amount: 0,
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
            {
                id: '2',
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_rate: 0,
                discount_amount: 0,
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
            {
                id: '3',
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_rate: 0,
                discount_amount: 0,
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
        ],
        subtotal: 0,
        discount_total: 0,
        tax_amount: 0,
        total: 0,
    });

    // Handle workspace switching
    const handleWorkspaceSwitch = (workspaceId: string) => {
        setData('workspace_id', parseInt(workspaceId));

        // Trigger full reload to get updated stock data
        router.visit('/quotations/create', {
            method: 'get',
            data: { workspace_id: workspaceId },
            preserveState: false,
        });
    };

    // Handle document subtype change and trigger partial reload
    const handleDocumentSubtypeChange = (value: string) => {
        const subtypeId = parseInt(value);
        setData('document_subtype_id', subtypeId);

        // Trigger partial reload to get NCF
        router.reload({
            only: ['ncf'],
            data: { document_subtype_id: subtypeId },
        });
    };

    // Handle payment term change and auto-calculate due date
    const handlePaymentTermChange = (value: string) => {
        setData((prev) => ({
            ...prev,
            payment_term: value,
            due_date: calculateDueDate(prev.issue_date, value),
        }));
    };

    // Handle manual due date change and set payment term to manual
    const handleDueDateChange = (value: string) => {
        setData((prev) => ({
            ...prev,
            due_date: value,
            payment_term: 'manual',
        }));
    };

    // Handle issue date change and recalculate due date if not manual
    const handleIssueDateChange = (value: string) => {
        setData((prev) => ({
            ...prev,
            issue_date: value,
            due_date: prev.payment_term === 'manual' ? prev.due_date : calculateDueDate(value, prev.payment_term),
        }));
    };

    // Add new quotation item
    const addItem = () => {
        const newItemId = itemId + 1;
        setItemId(newItemId);

        setData('items', [
            ...data.items,
            {
                id: newItemId.toString(),
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
                discount_rate: 0,
                discount_amount: 0,
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
        ]);
    };

    // Remove quotation item
    const removeItem = (itemId: string) => {
        if (data.items.length > 1) {
            setData('items', data.items.filter((item) => item.id !== itemId));
            calculateTotals(data.items.filter((item) => item.id !== itemId));
        }
    };

    // Update item data
    const updateItem = (itemId: string, field: keyof QuotationItem, value: any) => {
        const updatedItems = data.items.map((item) => {
            if (item.id === itemId) {
                const updatedItem = { ...item, [field]: value };

                // Recalculate item totals
                if (field === 'quantity' || field === 'unit_price' || field === 'discount_rate' || field === 'tax_rate') {
                    const lineSubtotal = updatedItem.quantity * updatedItem.unit_price;
                    updatedItem.discount_amount = lineSubtotal * (updatedItem.discount_rate / 100);
                    const discountedSubtotal = lineSubtotal - updatedItem.discount_amount;
                    updatedItem.tax_amount = discountedSubtotal * (updatedItem.tax_rate / 100);
                    updatedItem.total = discountedSubtotal; // Line total without tax
                }

                return updatedItem;
            }
            return item;
        });

        setData('items', updatedItems);
        calculateTotals(updatedItems);
    };

    // Get selected product for an item
    const getSelectedProduct = (item: QuotationItem): Product | null => {
        if (!item.product_id) return null;
        return products.find(p => p.id === item.product_id) || null;
    };

    // Get stock warning for a specific item
    const getStockWarning = (item: QuotationItem): { hasWarning: boolean; message: string; type: 'error' | 'warning' } | null => {
        const product = getSelectedProduct(item);
        if (!product || !product.track_stock) return null;

        if (product.stock_status === 'out_of_stock') {
            return {
                hasWarning: true,
                message: `${product.name} está agotado`,
                type: 'error'
            };
        }

        if (product.stock_quantity !== undefined && item.quantity > product.stock_quantity) {
            return {
                hasWarning: true,
                message: `Stock insuficiente. Disponible: ${product.stock_quantity}`,
                type: 'error'
            };
        }

        if (product.stock_status === 'low_stock') {
            return {
                hasWarning: true,
                message: `Stock bajo. Disponible: ${product.stock_quantity}`,
                type: 'warning'
            };
        }

        return null;
    };

    // Get stock status styling
    const getStockStatusColor = (status: string) => {
        switch (status) {
            case 'out_of_stock':
                return 'text-red-600 bg-red-50 border-red-200';
            case 'low_stock':
                return 'text-yellow-600 bg-yellow-50 border-yellow-200';
            case 'in_stock':
                return 'text-green-600 bg-green-50 border-green-200';
            case 'not_tracked':
                return 'text-gray-600 bg-gray-50 border-gray-200';
            default:
                return 'text-gray-600 bg-gray-50 border-gray-200';
        }
    };

    const getStockStatusText = (product: Product) => {
        if (!product.track_stock) {
            return 'No rastreado';
        }

        switch (product.stock_status) {
            case 'out_of_stock':
                return 'Sin stock';
            case 'low_stock':
                return `Stock bajo (${product.stock_quantity})`;
            case 'in_stock':
                return `En stock (${product.stock_quantity})`;
            default:
                return 'Desconocido';
        }
    };

    // Handle product selection - Fixed to prevent clearing
    const handleProductSelect = (itemId: string, productId: string) => {
        const product = products.find((p) => p.id === parseInt(productId));
        if (product) {
            const updatedItems = data.items.map((item) => {
                if (item.id === itemId) {
                    const updatedItem = {
                        ...item,
                        product_id: product.id,
                        description: product.name,
                        unit_price: product.price,
                        tax_rate: product.default_tax ? product.default_tax.rate : item.tax_rate
                    };

                    // Recalculate totals
                    const lineSubtotal = updatedItem.quantity * updatedItem.unit_price;
                    updatedItem.discount_amount = lineSubtotal * (updatedItem.discount_rate / 100);
                    const discountedSubtotal = lineSubtotal - updatedItem.discount_amount;
                    updatedItem.tax_amount = discountedSubtotal * (updatedItem.tax_rate / 100);
                    updatedItem.total = discountedSubtotal; // Line total without tax

                    return updatedItem;
                }
                return item;
            });

            setData('items', updatedItems);
            calculateTotals(updatedItems);
        }
    };

    const calculateTotals = (items: QuotationItem[]) => {
        // Calculate raw subtotal (before discounts)
        const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

        // Calculate total discounts
        const discountTotal = items.reduce((sum, item) => sum + item.discount_amount, 0);

        // Calculate total taxes
        const taxAmount = items.reduce((sum, item) => sum + item.tax_amount, 0);

        // Calculate final total: subtotal - discounts + taxes
        const total = subtotal - discountTotal + taxAmount;

        setData((prev) => ({
            ...prev,
            subtotal,
            discount_total: discountTotal,
            tax_amount: taxAmount,
            total,
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();


        post('/quotations', {
            onSuccess: () => {
                router.visit('/quotations');
            },
        });
    };

    const handleContactCreated = (newContact: Contact) => {
        // Add the new contact to the list
        setContactsList(prev => [...prev, newContact]);
        // Auto-select the new contact
        setData('contact_id', newContact.id);
        setSelectedContact(newContact);
    };

    const handleContactSelect = (contactId: string) => {
        const contact = contactsList.find(c => c.id === parseInt(contactId));
        setData('contact_id', parseInt(contactId));
        setSelectedContact(contact || null);
    };

    // Helper function to convert contacts to SearchableSelectOption format
    const contactOptions: SearchableSelectOption[] = contactsList.map((contact) => ({
        value: contact.id.toString(),
        label: contact.name,
    }));

    // Helper function to convert products to SearchableSelectOption format
    const getProductOptions = (): SearchableSelectOption[] => products.map((product) => ({
        value: product.id.toString(),
        label: `${product.name} - ${formatCurrency(product.price)}`,
        disabled: product.track_stock && product.stock_status === 'out_of_stock',
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva cotización" />

            <div className="min-h-screen bg-gray-50/30">
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardContent className="px-6 py-6">
                                <div className="flex items-start justify-between">
                                    {/* Company Info */}
                                    <div className="space-y-1">
                                        <h1 className="text-2xl font-bold text-gray-900">Centro Óptico Visión Integral</h1>
                                        <p className="text-sm text-gray-600">RNC o Cédula: 130382573</p>
                                        <p className="text-sm text-gray-600">info@covi.com.do</p>
                                    </div>

                                    {/* Quotation Details */}
                                    <div className="text-right space-y-1">
                                        <h2 className="text-xl font-bold text-gray-900">Cotización No. 1</h2>
                                        <div className="flex items-center gap-2 justify-end">
                                            <span className="text-sm font-medium text-gray-600">Numeración</span>
                                            <span className="text-sm font-mono bg-gray-100 px-2 py-1 rounded">
                                                {ncf || 'N/A'}
                                            </span>
                                            <button type="button" className="text-gray-400 hover:text-gray-600">
                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                        {/* Document Subtype Selection */}
                                        <div className="space-y-3 flex flex-col justify-end">
                                            <Label className="text-sm font-medium text-gray-900 gap-1">
                                                Tipo de documento
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <Select
                                                value={data.document_subtype_id?.toString() || ''}
                                                onValueChange={handleDocumentSubtypeChange}
                                            >
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
                                            {errors.document_subtype_id && (
                                                <p className="text-sm text-red-600">{errors.document_subtype_id}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Customer and Document Details - Cleaner Layout */}
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardContent className="px-6 py-6">
                                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    {/* Left Column - Customer Details */}
                                    <div className="space-y-6">
                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
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
                                                            <Plus className="h-4 w-4 mr-1" />
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
                                                    className="h-10 px-3 border-gray-300 text-primary hover:bg-primary/10"
                                                >
                                                    <Plus className="h-4 w-4 mr-1" />
                                                    Nuevo contacto
                                                </Button>
                                            </div>
                                            {errors.contact_id && (
                                                <p className="text-sm text-red-600">{errors.contact_id}</p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900">
                                                RNC o Cédula
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    value={selectedContact?.identification_number || ''}
                                                    placeholder="RNC o número de cédula"
                                                    className="h-10 border-gray-300"
                                                    readOnly
                                                    disabled
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900">
                                                Teléfono
                                            </Label>
                                            <Input
                                                value={selectedContact?.phone_primary || ''}
                                                placeholder="Número de teléfono"
                                                className="h-10 border-gray-300"
                                                readOnly
                                                disabled
                                            />
                                        </div>
                                    </div>

                                    {/* Right Column - quotation Details */}
                                    <div className="space-y-6">
                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                                Fecha
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    type="date"
                                                    value={data.issue_date}
                                                    onChange={(e) => handleIssueDateChange(e.target.value)}
                                                    className={`h-10 border-gray-300 ${errors.issue_date ? 'border-red-300' : ''}`}
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    ×
                                                </button>
                                                <button
                                                    type="button"
                                                    className="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            {errors.issue_date && (
                                                <p className="text-sm text-red-600">{errors.issue_date}</p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900">
                                                Plazo de pago
                                            </Label>
                                            <Select
                                                value={data.payment_term}
                                                onValueChange={handlePaymentTermChange}
                                            >
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
                                            <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                                Vencimiento
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    type="date"
                                                    value={data.due_date}
                                                    onChange={(e) => handleDueDateChange(e.target.value)}
                                                    className={`h-10 border-gray-300 ${errors.due_date ? 'border-red-300' : ''}`}
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    ×
                                                </button>
                                                <button
                                                    type="button"
                                                    className="absolute right-8 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                            {errors.due_date && (
                                                <p className="text-sm text-red-600">{errors.due_date}</p>
                                            )}
                                        </div>


                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* quotation Items - Enhanced */}
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                                <ShoppingCart className="h-4 w-4" />
                                            </div>
                                            Líneas de productos
                                        </CardTitle>
                                        <CardDescription className="mt-1 text-sm text-gray-600">
                                            Agrega los productos o servicios incluidos en esta cotización.
                                        </CardDescription>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addItem}
                                        className="flex items-center gap-2 border-primary bg-background text-primary hover:bg-primary/10 hover:border-primary"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Agregar línea
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="space-y-6">
                                    {/* Enhanced Table Header */}
                                    <div className="hidden lg:grid lg:grid-cols-12 gap-3 text-xs font-semibold text-gray-700 uppercase tracking-wider bg-gray-50 px-4 py-3 rounded-lg border">
                                        <div className="col-span-2">Producto</div>
                                        <div className="col-span-2">Descripción</div>
                                        <div className="col-span-1 text-center">Cant.</div>
                                        <div className="col-span-1 text-right">Precio unit.</div>
                                        <div className="col-span-1 text-right">Desc. (%)</div>
                                        <div className="col-span-1 text-right">Tax (%)</div>
                                        <div className="col-span-2 text-right">Total</div>
                                        <div className="col-span-2"></div>
                                    </div>

                                    {/* Enhanced Items */}
                                    <div className="space-y-4">
                                        {data.items.map((item, index) => (
                                            <div key={item.id} className="relative bg-gray-50/50 border border-gray-200 rounded-xl p-4 lg:p-0 lg:bg-transparent lg:border-0">
                                                {/* Mobile/Small screen layout */}
                                                <div className="lg:hidden space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <h4 className="text-sm font-medium text-gray-900">Línea {index + 1}</h4>
                                                        {data.items.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeItem(item.id)}
                                                                className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>

                                                    <div className="space-y-3">
                                                        <div>
                                                            <Label className="text-xs font-medium text-gray-700">Producto</Label>
                                                            <SearchableSelect
                                                                options={getProductOptions()}
                                                                value={item.product_id?.toString() || ''}
                                                                onValueChange={(value) => handleProductSelect(item.id, value)}
                                                                placeholder="Seleccionar producto"
                                                                searchPlaceholder="Buscar producto..."
                                                                emptyText="No se encontró ningún producto."
                                                                triggerClassName="h-10 mt-1"
                                                            />
                                                        </div>

                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                <Label className="text-xs font-medium text-gray-700">Descripción</Label>
                                                                {(() => {
                                                                    const product = getSelectedProduct(item);
                                                                    if (product && product.track_stock) {
                                                                        return (
                                                                            <div className={`text-xs px-2 py-0.5 rounded-full border ${getStockStatusColor(product.stock_status || 'not_tracked')}`}>
                                                                                {getStockStatusText(product)}
                                                                            </div>
                                                                        );
                                                                    }
                                                                    return null;
                                                                })()}
                                                            </div>
                                                            <Input
                                                                placeholder="Descripción del producto"
                                                                value={item.description}
                                                                onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                                className="h-10 mt-1"
                                                                disabled={!item.product_id}
                                                            />
                                                        </div>

                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <div className="flex items-center gap-2">
                                                                    <Label className="text-xs font-medium text-gray-700">Cantidad</Label>
                                                                    {(() => {
                                                                        const warning = getStockWarning(item);
                                                                        if (warning) {
                                                                            return (
                                                                                <div className="relative group">
                                                                                    <AlertTriangle
                                                                                        className={`h-4 w-4 cursor-help ${warning.type === 'error' ? 'text-red-500' : 'text-yellow-500'}`}
                                                                                    />
                                                                                    <div className="absolute left-1/2 transform -translate-x-1/2 bottom-full mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
                                                                                        {warning.message}
                                                                                        <div className="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                                                                    </div>
                                                                                </div>
                                                                            );
                                                                        }
                                                                        return null;
                                                                    })()}
                                                                </div>
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    value={item.quantity}
                                                                    onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                                    className={`h-10 mt-1 ${(() => {
                                                                        const warning = getStockWarning(item);
                                                                        if (warning?.type === 'error') return 'border-red-300 ring-red-500/20';
                                                                        if (warning?.type === 'warning') return 'border-yellow-300 ring-yellow-500/20';
                                                                        return '';
                                                                    })()}`}
                                                                    disabled={!item.product_id}
                                                                />
                                                                {(() => {
                                                                    const warning = getStockWarning(item);
                                                                    if (warning) {
                                                                        return (
                                                                            <div className={`mt-1 text-xs flex items-center gap-1 ${warning.type === 'error' ? 'text-red-600' : 'text-yellow-600'}`}>
                                                                                <AlertTriangle className="h-3 w-3" />
                                                                                {warning.message}
                                                                            </div>
                                                                        );
                                                                    }
                                                                    return null;
                                                                })()}
                                                            </div>
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Precio unit.</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="0"
                                                                    step="0.01"
                                                                    value={item.unit_price}
                                                                    onChange={(e) => updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)}
                                                                    className="h-10 mt-1"
                                                                    disabled={!item.product_id}
                                                                />
                                                            </div>
                                                        </div>

                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Descuento (%)</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="0"
                                                                    max="100"
                                                                    step="0.01"
                                                                    value={item.discount_rate}
                                                                    onChange={(e) => updateItem(item.id, 'discount_rate', parseFloat(e.target.value) || 0)}
                                                                    className="h-10 mt-1"
                                                                    disabled={!item.product_id}
                                                                />
                                                            </div>
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Impuesto (%)</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="0"
                                                                    max="100"
                                                                    step="0.01"
                                                                    value={item.tax_rate}
                                                                    onChange={(e) => updateItem(item.id, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                                    className="h-10 mt-1"
                                                                    disabled={!item.product_id}
                                                                />
                                                            </div>
                                                        </div>

                                                        <div className="grid grid-cols-1 gap-3">
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Total</Label>
                                                                <Input
                                                                    value={formatCurrency(item.total)}
                                                                    disabled
                                                                    className="h-10 mt-1 bg-gray-100 font-semibold text-gray-900"
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Desktop layout */}
                                                <div className="hidden lg:grid lg:grid-cols-12 gap-3 items-center py-3 border-b border-gray-100 last:border-b-0">
                                                    {/* Product Selection */}
                                                    <div className="col-span-2">
                                                        <SearchableSelect
                                                            options={getProductOptions()}
                                                            value={item.product_id?.toString() || ''}
                                                            onValueChange={(value) => handleProductSelect(item.id, value)}
                                                            placeholder="Seleccionar..."
                                                            searchPlaceholder="Buscar producto..."
                                                            emptyText="No se encontró ningún producto."
                                                            triggerClassName="h-9 border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                        />
                                                    </div>

                                                    {/* Description */}
                                                    <div className="col-span-2">
                                                        <Input
                                                            placeholder="Descripción..."
                                                            value={item.description}
                                                            onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                            className="h-9 border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Quantity */}
                                                    <div className="col-span-1 relative">
                                                        <div className="relative">
                                                            <Input
                                                                type="number"
                                                                min="1"
                                                                step="1"
                                                                value={item.quantity}
                                                                onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                                className={`h-9 text-center border-gray-200 focus:border-blue-500 focus:ring-blue-500/20 ${(() => {
                                                                    const warning = getStockWarning(item);
                                                                    if (warning?.type === 'error') return 'border-red-300 ring-red-500/20';
                                                                    if (warning?.type === 'warning') return 'border-yellow-300 ring-yellow-500/20';
                                                                    return '';
                                                                })()}`}
                                                                disabled={!item.product_id}
                                                            />
                                                            {(() => {
                                                                const warning = getStockWarning(item);
                                                                if (warning) {
                                                                    return (
                                                                        <div className="absolute -top-1 -right-1 group">
                                                                            <AlertTriangle
                                                                                className={`h-4 w-4 cursor-help ${warning.type === 'error' ? 'text-red-500' : 'text-yellow-500'}`}
                                                                            />
                                                                            <div className="absolute left-1/2 transform -translate-x-1/2 bottom-full mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
                                                                                {warning.message}
                                                                                <div className="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                                                            </div>
                                                                        </div>
                                                                    );
                                                                }
                                                                return null;
                                                            })()}
                                                        </div>
                                                    </div>

                                                    {/* Unit Price */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)}
                                                            className="h-9 text-right border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Discount Rate */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            value={item.discount_rate}
                                                            onChange={(e) => updateItem(item.id, 'discount_rate', parseFloat(e.target.value) || 0)}
                                                            className="h-9 text-right border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Tax Rate */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            value={item.tax_rate}
                                                            onChange={(e) => updateItem(item.id, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                            className="h-9 text-right border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Total */}
                                                    <div className="col-span-2">
                                                        <Input
                                                            value={formatCurrency(item.total)}
                                                            disabled
                                                            className="h-9 text-right bg-gray-50 font-semibold text-gray-900 border-gray-200"
                                                        />
                                                    </div>

                                                    {/* Remove Button */}
                                                    <div className="col-span-2 flex justify-end">
                                                        {data.items.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeItem(item.id)}
                                                                className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Enhanced Totals */}
                                    <div className="border-t border-gray-200 pt-6">
                                        <div className="flex justify-end">
                                            <div className="w-full max-w-sm space-y-3">
                                                <div className="flex justify-between items-center text-sm">
                                                    <span className="text-gray-600">Subtotal:</span>
                                                    <span className="font-medium text-gray-900">{formatCurrency(data.subtotal)}</span>
                                                </div>
                                                {data.discount_total > 0 && (
                                                    <div className="flex justify-between items-center text-sm">
                                                        <span className="text-gray-600">Descuentos:</span>
                                                        <span className="font-medium text-red-600">-{formatCurrency(data.discount_total)}</span>
                                                    </div>
                                                )}
                                                <div className="flex justify-between items-center text-sm">
                                                    <span className="text-gray-600">Impuestos:</span>
                                                    <span className="font-medium text-gray-900">+{formatCurrency(data.tax_amount)}</span>
                                                </div>
                                                <div className="flex justify-between items-center text-lg font-bold border-t border-gray-200 pt-3">
                                                    <span className="text-gray-900">Total:</span>
                                                    <span className="text-primary">{formatCurrency(data.total)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Notes - Enhanced */}
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                        <FileText className="h-4 w-4" />
                                    </div>
                                    Notas adicionales
                                </CardTitle>
                                <CardDescription className="mt-1 text-sm text-gray-600">
                                    Información adicional que aparecerá en la cotización (opcional).
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
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
                                        className="resize-none border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                    />
                                    <p className="text-xs text-gray-500">
                                        Estas notas aparecerán al final de la cotización
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Enhanced Actions */}
                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                size="lg"
                                asChild
                                className="border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:border-gray-400"
                            >
                                <a href="/quotations" className="flex items-center justify-center gap-2">
                                    Cancelar
                                </a>
                            </Button>
                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing || !ncf || !data.contact_id || data.items.length === 0}
                                className={`flex items-center justify-center gap-2 min-w-[160px] ${
                                    processing || !ncf
                                        ? 'bg-gray-400 hover:bg-gray-400'
                                        : 'bg-primary hover:bg-primary/90'
                                }`}
                            >
                                {processing ? (
                                    <>
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                        Guardando...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4" />
                                        Guardar cotización
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <QuickContactModal
                open={showContactModal}
                onOpenChange={setShowContactModal}
                onSuccess={handleContactCreated}
                onAdvancedForm={() => {
                    router.visit('/contacts/create');
                }}
            />
        </AppLayout>
    );
}
