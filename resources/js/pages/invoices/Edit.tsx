import { Head, router, useForm } from '@inertiajs/react';
import { Building2, FileText, Plus, Save, ShoppingCart } from 'lucide-react';
import { useEffect, useState } from 'react';

import QuickContactModal from '@/components/contacts/quick-contact-modal';
import { EditNcfModal } from '@/components/invoices/edit-ncf-modal';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Product, type Workspace } from '@/types';
import { useCurrency } from '@/utils/currency';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Editar factura',
        href: '#',
    },
];

interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    next_number: number;
}

interface InvoiceItem {
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
    items: InvoiceItem[];
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
}

interface Invoice {
    id: number;
    document_number: string;
    ncf: string;
    contact_id: number;
    workspace_id: number;
    document_subtype_id: number;
    issue_date: string;
    due_date: string;
    payment_term: string;
    notes: string;
    subtotal: number;
    discount_total: number;
    tax_amount: number;
    total: number;
    status: string;
    contact: Contact;
    document_subtype: DocumentSubtype;
    workspace: Workspace;
    items: Array<{
        id: number;
        product_id: number | null;
        description: string;
        quantity: number;
        unit_price: number;
        discount_rate: number;
        discount_amount: number;
        tax_rate: number;
        tax_amount: number;
        total: number;
        product?: Product;
    }>;
}

interface Props {
    invoice: Invoice;
    documentSubtypes: DocumentSubtype[];
    customers: Contact[];
    products: Product[];
    currentWorkspace?: Workspace | null;
    availableWorkspaces?: Workspace[];
    ncf?: string | null;
}

export default function EditInvoice({ invoice, documentSubtypes, customers, products, currentWorkspace, availableWorkspaces, ncf }: Props) {
    const [itemId, setItemId] = useState(invoice.items.length > 0 ? Math.max(...invoice.items.map((item) => parseInt(item.id.toString()))) + 1 : 1);
    const [showContactModal, setShowContactModal] = useState(false);
    const [showNcfModal, setShowNcfModal] = useState(false);
    const [contactsList, setContactsList] = useState<Contact[]>(customers);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(invoice.contact);

    const { format: formatCurrency } = useCurrency();

    const calculateDueDate = (issueDate: string, paymentTerm: string): string => {
        if (!issueDate || paymentTerm === 'manual') return '';

        const date = new Date(issueDate);
        const daysToAdd = parseInt(paymentTerm.replace('days', ''));

        if (isNaN(daysToAdd)) return '';

        date.setDate(date.getDate() + daysToAdd);
        return date.toISOString().split('T')[0];
    };

    // Convert invoice items to the format expected by the form
    const convertInvoiceItems = (items: Invoice['items']): InvoiceItem[] => {
        return items.map((item, index) => ({
            id: (index + 1).toString(),
            product_id: item.product_id,
            description: item.description || '',
            quantity: item.quantity || 1,
            unit_price: item.unit_price || 0,
            discount_rate: item.discount_rate || 0,
            discount_amount: item.discount_amount || 0,
            tax_rate: item.tax_rate || 0,
            tax_amount: item.tax_amount || 0,
            total: item.total || 0,
        }));
    };

    // Helper function to format date for HTML date input
    const formatDateForInput = (dateString: string): string => {
        if (!dateString) return '';
        // Handle both ISO format and already formatted dates
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toISOString().split('T')[0];
    };

    const { data, setData, put, processing, errors } = useForm<FormData>({
        document_subtype_id: invoice.document_subtype_id,
        contact_id: invoice.contact_id,
        workspace_id: invoice.workspace_id,
        issue_date: formatDateForInput(invoice.issue_date),
        due_date: formatDateForInput(invoice.due_date),
        payment_term: invoice.payment_term,
        notes: invoice.notes || '',
        ncf: ncf || invoice.ncf || '',
        items: convertInvoiceItems(invoice.items),
        subtotal: invoice.subtotal,
        discount_total: invoice.discount_total,
        tax_amount: invoice.tax_amount,
        total: invoice.total,
    });

    // Recalculate totals when component mounts and items are loaded
    useEffect(() => {
        // Use setTimeout to ensure this runs after the form data is set
        const timeoutId = setTimeout(() => {
            if (data.items && data.items.length > 0) {
                calculateTotals(data.items);
            }
        }, 0);

        return () => clearTimeout(timeoutId);
    }, []); // Only run once on mount

    // Update NCF when it changes from the server
    useEffect(() => {
        if (ncf && ncf !== data.ncf) {
            setData('ncf', ncf);
        }
    }, [ncf]);

    // Handle workspace switching
    const handleWorkspaceSwitch = (workspaceId: string) => {
        setData('workspace_id', parseInt(workspaceId));

        // Trigger full reload to get updated stock data
        router.visit(`/invoices/${invoice.id}/edit`, {
            method: 'get',
            data: { workspace_id: workspaceId },
            preserveState: false,
        });
    };

    // Handle document subtype change and trigger partial reload
    const handleDocumentSubtypeChange = (value: string) => {
        const subtypeId = parseInt(value);
        setData('document_subtype_id', subtypeId);

        // Trigger partial reload to get new NCF
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

    // Add new invoice item
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

    // Remove invoice item
    const removeItem = (itemId: string) => {
        if (data.items.length > 1) {
            setData(
                'items',
                data.items.filter((item) => item.id !== itemId),
            );
            calculateTotals(data.items.filter((item) => item.id !== itemId));
        }
    };

    // Update item data
    const updateItem = (itemId: string, field: keyof InvoiceItem, value: any) => {
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
    const getSelectedProduct = (item: InvoiceItem): Product | null => {
        if (!item.product_id) return null;
        return products.find((p) => p.id === item.product_id) || null;
    };

    // Get stock warning for a specific item
    const getStockWarning = (item: InvoiceItem): { hasWarning: boolean; message: string; type: 'error' | 'warning' } | null => {
        const product = getSelectedProduct(item);
        if (!product || !product.track_stock) return null;

        if (product.stock_status === 'out_of_stock') {
            return {
                hasWarning: true,
                message: `${product.name} está agotado`,
                type: 'error',
            };
        }

        if (product.stock_quantity !== undefined && item.quantity > product.stock_quantity) {
            return {
                hasWarning: true,
                message: `Stock insuficiente. Disponible: ${product.stock_quantity}`,
                type: 'error',
            };
        }

        if (product.stock_status === 'low_stock') {
            return {
                hasWarning: true,
                message: `Stock bajo. Disponible: ${product.stock_quantity}`,
                type: 'warning',
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

    // Handle product selection
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
                        tax_rate: product.default_tax ? product.default_tax.rate : item.tax_rate,
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

    const calculateTotals = (items: InvoiceItem[]) => {
        // Calculate raw subtotal (before discounts)
        const subtotal = items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);

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

        put(`/invoices/${invoice.id}`, {
            onSuccess: () => {
                router.visit('/invoices');
            },
        });
    };

    const handleContactCreated = (newContact: Contact) => {
        // Add the new contact to the list
        setContactsList((prev) => [...prev, newContact]);
        // Auto-select the new contact
        setData('contact_id', newContact.id);
        setSelectedContact(newContact);
    };

    const handleContactSelect = (contactId: string) => {
        const contact = contactsList.find((c) => c.id === parseInt(contactId));
        setData('contact_id', parseInt(contactId));
        setSelectedContact(contact || null);
    };

    // Helper function to convert contacts to SearchableSelectOption format
    const contactOptions: SearchableSelectOption[] = contactsList.map((contact) => ({
        value: contact.id.toString(),
        label: contact.name,
    }));

    // Helper function to convert products to SearchableSelectOption format
    const getProductOptions = (): SearchableSelectOption[] =>
        products.map((product) => ({
            value: product.id.toString(),
            label: `${product.name} - ${formatCurrency(product.price)}`,
            disabled: product.track_stock && product.stock_status === 'out_of_stock',
        }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar factura ${invoice.document_number}`} />

            <div className="min-h-screen bg-gray-50/30">
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {/* Enhanced Header - Invoice Style */}
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

                                    {/* Invoice Details */}
                                    <div className="space-y-1 text-right">
                                        <h2 className="text-xl font-bold text-gray-900">Factura No. {invoice.id}</h2>
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
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        {/* Document Subtype Selection */}
                                        <div className="flex flex-col justify-end space-y-3">
                                            <Label className="gap-1 text-sm font-medium text-gray-900">
                                                Tipo de documento
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <Select value={data.document_subtype_id?.toString() || ''} onValueChange={handleDocumentSubtypeChange}>
                                                <SelectTrigger
                                                    className={`h-8 text-xs ${errors.document_subtype_id ? 'border-red-300' : 'border-gray-300'}`}
                                                >
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
                            </CardContent>
                        </Card>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Customer and Document Details - Cleaner Layout */}
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardContent className="px-6 py-6">
                                <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
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
                                                            className="text-blue-600 hover:bg-blue-50"
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
                                                    className="h-10 border-gray-300 px-3 text-blue-600 hover:bg-blue-50"
                                                >
                                                    <Plus className="mr-1 h-4 w-4" />
                                                    Nuevo contacto
                                                </Button>
                                            </div>
                                            {errors.contact_id && <p className="text-sm text-red-600">{errors.contact_id}</p>}
                                        </div>

                                        {/* Workspace Selection */}
                                        {availableWorkspaces && availableWorkspaces.length > 1 && (
                                            <div className="space-y-3">
                                                <Label className="flex items-center gap-1 text-sm font-medium text-gray-900">
                                                    <Building2 className="h-4 w-4" />
                                                    Espacio de Trabajo
                                                    <span className="text-red-500">*</span>
                                                </Label>
                                                <Select value={data.workspace_id?.toString() || ''} onValueChange={handleWorkspaceSwitch}>
                                                    <SelectTrigger
                                                        className={`h-10 ${errors.workspace_id ? 'border-red-300 ring-red-500/20' : 'border-gray-300'}`}
                                                    >
                                                        <SelectValue placeholder="Seleccionar espacio de trabajo" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {availableWorkspaces.map((workspace) => (
                                                            <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                                {workspace.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors.workspace_id && <p className="text-sm text-red-600">{errors.workspace_id}</p>}
                                            </div>
                                        )}

                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900">RNC o Cédula</Label>
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
                                                    className="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
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
                                    </div>

                                    {/* Right Column - Invoice Details */}
                                    <div className="space-y-6">
                                        <div className="space-y-3">
                                            <Label className="flex items-center gap-1 text-sm font-medium text-gray-900">
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
                                                    className="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    ×
                                                </button>
                                                <button
                                                    type="button"
                                                    className="absolute top-1/2 right-8 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
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
                                            <div className="relative">
                                                <Input
                                                    type="date"
                                                    value={data.due_date}
                                                    onChange={(e) => handleDueDateChange(e.target.value)}
                                                    className={`h-10 border-gray-300 ${errors.due_date ? 'border-red-300' : ''}`}
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute top-1/2 right-2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    ×
                                                </button>
                                                <button
                                                    type="button"
                                                    className="absolute top-1/2 right-8 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                            {errors.due_date && <p className="text-sm text-red-600">{errors.due_date}</p>}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Invoice Items - Enhanced */}
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
                                            Modifica los productos o servicios incluidos en esta factura.
                                        </CardDescription>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addItem}
                                        className="flex items-center gap-2 border-blue-200 bg-blue-50 text-blue-700 hover:border-blue-300 hover:bg-blue-100"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Agregar línea
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="space-y-6">
                                    {/* Enhanced Table Header */}
                                    <div className="hidden gap-3 rounded-lg border bg-gray-50 px-4 py-3 text-xs font-semibold tracking-wider text-gray-700 uppercase lg:grid lg:grid-cols-12">
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
                                            <div
                                                key={item.id}
                                                className="relative rounded-xl border border-gray-200 bg-gray-50/50 p-4 lg:border-0 lg:bg-transparent lg:p-0"
                                            >
                                                {/* Mobile/Small screen layout */}
                                                <div className="space-y-4 lg:hidden">
                                                    <div className="flex items-center justify-between">
                                                        <h4 className="text-sm font-medium text-gray-900">Línea {index + 1}</h4>
                                                        {data.items.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeItem(item.id)}
                                                                className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                            >
                                                                Eliminar
                                                            </Button>
                                                        )}
                                                    </div>

                                                    <div className="space-y-3">
                                                        <div>
                                                            <Label className="text-xs font-medium text-gray-700">Producto</Label>
                                                            <SearchableSelect
                                                                options={getProductOptions()}
                                                                value={item.product_id?.toString() || ''}
                                                                onValueChange={(value: string) => handleProductSelect(item.id, value)}
                                                                placeholder="Seleccionar producto"
                                                                searchPlaceholder="Buscar producto..."
                                                                emptyText="No se encontró ningún producto."
                                                                triggerClassName="h-10 mt-1"
                                                            />
                                                        </div>

                                                        <div>
                                                            <Label className="text-xs font-medium text-gray-700">Descripción</Label>
                                                            <Input
                                                                placeholder="Descripción..."
                                                                value={item.description || ''}
                                                                onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                                className="mt-1"
                                                                disabled={!item.product_id}
                                                            />
                                                        </div>

                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Cantidad</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    value={item.quantity?.toString() || '1'}
                                                                    onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                                    className="mt-1 text-center"
                                                                    disabled={!item.product_id}
                                                                />
                                                            </div>
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Precio unitario</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="0"
                                                                    step="0.01"
                                                                    value={item.unit_price?.toString() || '0'}
                                                                    onChange={(e) =>
                                                                        updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)
                                                                    }
                                                                    className="mt-1 text-right"
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
                                                                    value={item.discount_rate?.toString() || '0'}
                                                                    onChange={(e) =>
                                                                        updateItem(item.id, 'discount_rate', parseFloat(e.target.value) || 0)
                                                                    }
                                                                    className="mt-1 text-right"
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
                                                                    value={item.tax_rate?.toString() || '0'}
                                                                    onChange={(e) => updateItem(item.id, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                                    className="mt-1 text-right"
                                                                    disabled={!item.product_id}
                                                                />
                                                            </div>
                                                        </div>

                                                        <div>
                                                            <Label className="text-xs font-medium text-gray-700">Total de línea</Label>
                                                            <Input
                                                                value={formatCurrency(item.total)}
                                                                disabled
                                                                className="mt-1 bg-gray-50 text-right font-semibold"
                                                            />
                                                        </div>

                                                        {/* Stock warning for mobile */}
                                                        {(() => {
                                                            const stockWarning = getStockWarning(item);
                                                            if (!stockWarning) return null;
                                                            return (
                                                                <div
                                                                    className={`rounded border px-2 py-1 text-xs ${
                                                                        stockWarning.type === 'error'
                                                                            ? 'border-red-200 bg-red-50 text-red-600'
                                                                            : 'border-yellow-200 bg-yellow-50 text-yellow-600'
                                                                    }`}
                                                                >
                                                                    {stockWarning.message}
                                                                </div>
                                                            );
                                                        })()}
                                                    </div>
                                                </div>

                                                {/* Desktop layout */}
                                                <div className="hidden items-center gap-3 border-b border-gray-100 py-3 last:border-b-0 lg:grid lg:grid-cols-12">
                                                    {/* Product selection */}
                                                    <div className="col-span-2">
                                                        <SearchableSelect
                                                            options={getProductOptions()}
                                                            value={item.product_id?.toString() || ''}
                                                            onValueChange={(value: string) => handleProductSelect(item.id, value)}
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
                                                            value={item.description || ''}
                                                            onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                            className="h-9 border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Quantity with stock warning */}
                                                    <div className="relative col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            value={item.quantity?.toString() || '1'}
                                                            onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                            className="h-9 border-gray-200 text-center focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                        {(() => {
                                                            const stockWarning = getStockWarning(item);
                                                            if (!stockWarning) return null;
                                                            return (
                                                                <div className="absolute top-full right-0 left-0 z-10 mt-1">
                                                                    <div
                                                                        className={`rounded border px-2 py-1 text-xs shadow-sm ${
                                                                            stockWarning.type === 'error'
                                                                                ? 'border-red-200 bg-red-50 text-red-600'
                                                                                : 'border-yellow-200 bg-yellow-50 text-yellow-600'
                                                                        }`}
                                                                    >
                                                                        {stockWarning.message}
                                                                    </div>
                                                                </div>
                                                            );
                                                        })()}
                                                    </div>

                                                    {/* Unit price */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={item.unit_price?.toString() || '0'}
                                                            onChange={(e) => updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)}
                                                            className="h-9 border-gray-200 text-right focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Discount rate */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            value={item.discount_rate?.toString() || '0'}
                                                            onChange={(e) => updateItem(item.id, 'discount_rate', parseFloat(e.target.value) || 0)}
                                                            className="h-9 border-gray-200 text-right focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
                                                        />
                                                    </div>

                                                    {/* Tax rate */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            step="0.01"
                                                            value={item.tax_rate?.toString() || '0'}
                                                            onChange={(e) => updateItem(item.id, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                            className="h-9 border-gray-200 text-right focus:border-blue-500 focus:ring-blue-500/20"
                                                            disabled={!item.product_id}
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

                                                    {/* Actions */}
                                                    <div className="col-span-2 flex justify-end">
                                                        {data.items.length > 1 && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeItem(item.id)}
                                                                className="text-red-600 hover:bg-red-50 hover:text-red-700"
                                                            >
                                                                Eliminar
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
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-600">Subtotal:</span>
                                                    <span className="font-medium text-gray-900">{formatCurrency(data.subtotal)}</span>
                                                </div>
                                                {data.discount_total > 0 && (
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-gray-600">Descuentos:</span>
                                                        <span className="font-medium text-gray-900">-{formatCurrency(data.discount_total)}</span>
                                                    </div>
                                                )}
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="text-gray-600">Impuestos:</span>
                                                    <span className="font-medium text-gray-900">+{formatCurrency(data.tax_amount)}</span>
                                                </div>
                                                <div className="flex items-center justify-between border-t border-gray-200 pt-3 text-lg font-bold">
                                                    <span className="text-gray-900">Total:</span>
                                                    <span className="text-blue-600">{formatCurrency(data.total)}</span>
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
                                    Información adicional que aparecerá en la factura (opcional).
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
                                    <p className="text-xs text-gray-500">Estas notas aparecerán al final de la factura</p>
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
                                className="border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50"
                            >
                                <a href="/invoices" className="flex items-center justify-center gap-2">
                                    Cancelar
                                </a>
                            </Button>

                            <Button
                                type="submit"
                                size="lg"
                                disabled={processing || !data.contact_id || data.items.length === 0}
                                className={`flex min-w-[160px] items-center justify-center gap-2 ${
                                    processing ? 'bg-gray-400 hover:bg-gray-400' : 'bg-primary hover:bg-primary/90'
                                }`}
                            >
                                {processing ? (
                                    <>
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                        Actualizando...
                                    </>
                                ) : (
                                    <>
                                        <Save className="h-4 w-4" />
                                        Actualizar factura
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

            <EditNcfModal
                isOpen={showNcfModal}
                onClose={() => setShowNcfModal(false)}
                currentNcf={data.ncf}
                prefix={documentSubtypes.find((d) => d.id === data.document_subtype_id)?.prefix || ''}
                nextNumber={documentSubtypes.find((d) => d.id === data.document_subtype_id)?.next_number || 0}
                onSave={(newNcf) => setData('ncf', newNcf)}
                invoiceId={invoice.id}
            />
        </AppLayout>
    );
}
