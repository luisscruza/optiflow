import { Head, router, useForm } from '@inertiajs/react';
import { Calendar, FileText, Plus, Save, ShoppingCart, User, Receipt, Hash, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Product } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facturas',
        href: '/invoices',
    },
    {
        title: 'Nueva factura',
        href: '/invoices/create',
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
    tax_rate: number;
    tax_amount: number;
    total: number;
}

interface FormData {
    document_subtype_id: number | null;
    contact_id: number | null;
    issue_date: string;
    due_date: string;
    notes: string;
    items: InvoiceItem[];
    subtotal: number;
    tax_amount: number;
    total: number;
}

interface Props {
    documentSubtypes: DocumentSubtype[];
    customers: Contact[];
    products: Product[];
    ncf?: string | null;
}

export default function CreateInvoice({ documentSubtypes, customers, products, ncf }: Props) {
    const [itemId, setItemId] = useState(1);

    const { data, setData, post, processing, errors } = useForm<FormData>({
        document_subtype_id: null,
        contact_id: null,
        issue_date: new Date().toISOString().split('T')[0],
        due_date: '',
        notes: '',
        items: [
            {
                id: '1',
                product_id: null,
                description: '',
                quantity: 1,
                unit_price: 0,
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
        ],
        subtotal: 0,
        tax_amount: 0,
        total: 0,
    });

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
                tax_rate: 0,
                tax_amount: 0,
                total: 0,
            },
        ]);
    };

    // Remove invoice item
    const removeItem = (itemId: string) => {
        if (data.items.length > 1) {
            setData('items', data.items.filter((item) => item.id !== itemId));
            calculateTotals(data.items.filter((item) => item.id !== itemId));
        }
    };

    // Update item data
    const updateItem = (itemId: string, field: keyof InvoiceItem, value: any) => {
        const updatedItems = data.items.map((item) => {
            if (item.id === itemId) {
                const updatedItem = { ...item, [field]: value };

                // Recalculate item totals
                if (field === 'quantity' || field === 'unit_price' || field === 'tax_rate') {
                    const subtotal = updatedItem.quantity * updatedItem.unit_price;
                    updatedItem.tax_amount = subtotal * (updatedItem.tax_rate / 100);
                    updatedItem.total = subtotal + updatedItem.tax_amount;
                }

                return updatedItem;
            }
            return item;
        });

        setData('items', updatedItems);
        calculateTotals(updatedItems);
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
                    const subtotal = updatedItem.quantity * updatedItem.unit_price;
                    updatedItem.tax_amount = subtotal * (updatedItem.tax_rate / 100);
                    updatedItem.total = subtotal + updatedItem.tax_amount;

                    return updatedItem;
                }
                return item;
            });

            setData('items', updatedItems);
            calculateTotals(updatedItems);
        }
    };

    // Calculate totals
    const calculateTotals = (items: InvoiceItem[]) => {
        const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        const taxAmount = items.reduce((sum, item) => sum + item.tax_amount, 0);
        const total = subtotal + taxAmount;

        setData((prev) => ({
            ...prev,
            subtotal,
            tax_amount: taxAmount,
            total,
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/invoices', {
            onSuccess: () => {
                router.visit('/invoices');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva factura" />

            <div className="min-h-screen bg-gray-50/30">
                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {/* Enhanced Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between">
                            <div className="space-y-1">
                                <h1 className="text-3xl font-bold tracking-tight text-gray-900">Nueva factura</h1>
                                <p className="text-base text-gray-600">Crea una nueva factura para tu cliente de forma rápida y sencilla.</p>
                            </div>
                            <div className="hidden sm:block">
                                <div className="flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700">
                                    <Receipt className="h-4 w-4" />
                                    Borrador
                                </div>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        {/* Invoice Header - Enhanced */}
                        <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                            <CardHeader className="bg-gray-50/50 px-6 py-5">
                                <CardTitle className="flex items-center gap-3 text-lg font-semibold text-gray-900">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                                        <FileText className="h-4 w-4" />
                                    </div>
                                    Información de la factura
                                </CardTitle>
                                <CardDescription className="mt-1 text-sm text-gray-600">
                                    Configure los datos principales de la factura.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="px-6 py-6">
                                <div className="space-y-8">
                                    {/* NCF and Numeración Row - Enhanced */}
                                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                        <div className="space-y-3">
                                            <Label htmlFor="document_subtype_id" className="text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <Hash className="h-4 w-4 text-gray-500" />
                                                    Numeración
                                                    <span className="text-red-500">*</span>
                                                </div>
                                            </Label>
                                            <Select
                                                value={data.document_subtype_id?.toString() || ''}
                                                onValueChange={handleDocumentSubtypeChange}
                                            >
                                                <SelectTrigger className={`h-11 ${errors.document_subtype_id ? 'border-red-300 ring-red-500/20' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20'}`}>
                                                    <SelectValue placeholder="Seleccionar tipo de numeración" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {documentSubtypes.map((subtype) => (
                                                        <SelectItem key={subtype.id} value={subtype.id.toString()}>
                                                            <div className="flex items-center justify-between w-full">
                                                                <span>{subtype.name}</span>
                                                                <span className="ml-2 text-xs text-gray-500">({subtype.prefix})</span>
                                                            </div>
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.document_subtype_id && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-red-500">•</span>
                                                    {errors.document_subtype_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <Label className="text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <Receipt className="h-4 w-4 text-gray-500" />
                                                    NCF
                                                </div>
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    value={ncf || 'Seleccionar numeración primero'}
                                                    disabled
                                                    className={`h-11 ${ncf 
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-medium' 
                                                        : 'bg-gray-50 text-gray-400 border-gray-200'
                                                    }`}
                                                />
                                                {ncf && (
                                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <div className="h-2 w-2 rounded-full bg-emerald-500"></div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>

                                    {/* Customer and Dates - Enhanced */}
                                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                                        <div className="space-y-3">
                                            <Label htmlFor="contact_id" className="text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <User className="h-4 w-4 text-gray-500" />
                                                    Cliente
                                                    <span className="text-red-500">*</span>
                                                </div>
                                            </Label>
                                            <Select
                                                value={data.contact_id?.toString() || ''}
                                                onValueChange={(value) => setData('contact_id', parseInt(value))}
                                            >
                                                <SelectTrigger className={`h-11 ${errors.contact_id ? 'border-red-300 ring-red-500/20' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20'}`}>
                                                    <SelectValue placeholder="Seleccionar cliente" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {customers.map((customer) => (
                                                        <SelectItem key={customer.id} value={customer.id.toString()}>
                                                            {customer.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.contact_id && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-red-500">•</span>
                                                    {errors.contact_id}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <Label htmlFor="issue_date" className="text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4 text-gray-500" />
                                                    Fecha de emisión
                                                    <span className="text-red-500">*</span>
                                                </div>
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    id="issue_date"
                                                    type="date"
                                                    value={data.issue_date}
                                                    onChange={(e) => setData('issue_date', e.target.value)}
                                                    className={`h-11 pl-4 ${errors.issue_date ? 'border-red-300 ring-red-500/20' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20'}`}
                                                />
                                            </div>
                                            {errors.issue_date && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-red-500">•</span>
                                                    {errors.issue_date}
                                                </p>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <Label htmlFor="due_date" className="text-sm font-medium text-gray-900">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="h-4 w-4 text-gray-500" />
                                                    Fecha de vencimiento
                                                </div>
                                            </Label>
                                            <div className="relative">
                                                <Input
                                                    id="due_date"
                                                    type="date"
                                                    value={data.due_date}
                                                    onChange={(e) => setData('due_date', e.target.value)}
                                                    className={`h-11 pl-4 ${errors.due_date ? 'border-red-300 ring-red-500/20' : 'border-gray-200 focus:border-blue-500 focus:ring-blue-500/20'}`}
                                                />
                                            </div>
                                            {errors.due_date && (
                                                <p className="text-sm text-red-600 flex items-center gap-1">
                                                    <span className="text-red-500">•</span>
                                                    {errors.due_date}
                                                </p>
                                            )}
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
                                            Agrega los productos o servicios incluidos en esta factura.
                                        </CardDescription>
                                    </div>
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={addItem}
                                        className="flex items-center gap-2 border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 hover:border-blue-300"
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
                                        <div className="col-span-2 text-right">Precio unit.</div>
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
                                                            <Select
                                                                value={item.product_id?.toString() || ''}
                                                                onValueChange={(value) => handleProductSelect(item.id, value)}
                                                            >
                                                                <SelectTrigger className="h-10 mt-1">
                                                                    <SelectValue placeholder="Seleccionar producto" />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {products.map((product) => (
                                                                        <SelectItem key={product.id} value={product.id.toString()}>
                                                                            <div className="flex flex-col">
                                                                                <span className="font-medium">{product.name}</span>
                                                                                <span className="text-xs text-gray-500">${product.price}</span>
                                                                            </div>
                                                                        </SelectItem>
                                                                    ))}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                        
                                                        <div>
                                                            <Label className="text-xs font-medium text-gray-700">Descripción</Label>
                                                            <Input
                                                                placeholder="Descripción del producto"
                                                                value={item.description}
                                                                onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                                className="h-10 mt-1"
                                                            />
                                                        </div>
                                                        
                                                        <div className="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Cantidad</Label>
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    value={item.quantity}
                                                                    onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                                    className="h-10 mt-1"
                                                                />
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
                                                                />
                                                            </div>
                                                        </div>
                                                        
                                                        <div className="grid grid-cols-2 gap-3">
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
                                                                />
                                                            </div>
                                                            <div>
                                                                <Label className="text-xs font-medium text-gray-700">Total</Label>
                                                                <Input
                                                                    value={`$${item.total.toFixed(2)}`}
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
                                                        <Select
                                                            value={item.product_id?.toString() || ''}
                                                            onValueChange={(value) => handleProductSelect(item.id, value)}
                                                        >
                                                            <SelectTrigger className="h-9 border-gray-200 focus:border-blue-500 focus:ring-blue-500/20">
                                                                <SelectValue placeholder="Seleccionar..." />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {products.map((product) => (
                                                                    <SelectItem key={product.id} value={product.id.toString()}>
                                                                        <div className="flex flex-col">
                                                                            <span className="font-medium">{product.name}</span>
                                                                            <span className="text-xs text-gray-500">${product.price}</span>
                                                                        </div>
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>

                                                    {/* Description */}
                                                    <div className="col-span-2">
                                                        <Input
                                                            placeholder="Descripción..."
                                                            value={item.description}
                                                            onChange={(e) => updateItem(item.id, 'description', e.target.value)}
                                                            className="h-9 border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                        />
                                                    </div>

                                                    {/* Quantity */}
                                                    <div className="col-span-1">
                                                        <Input
                                                            type="number"
                                                            min="1"
                                                            step="1"
                                                            value={item.quantity}
                                                            onChange={(e) => updateItem(item.id, 'quantity', parseInt(e.target.value) || 1)}
                                                            className="h-9 text-center border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
                                                        />
                                                    </div>

                                                    {/* Unit Price */}
                                                    <div className="col-span-2">
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(item.id, 'unit_price', parseFloat(e.target.value) || 0)}
                                                            className="h-9 text-right border-gray-200 focus:border-blue-500 focus:ring-blue-500/20"
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
                                                        />
                                                    </div>

                                                    {/* Total */}
                                                    <div className="col-span-2">
                                                        <Input
                                                            value={`$${item.total.toFixed(2)}`}
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
                                                    <span className="font-medium text-gray-900">${data.subtotal.toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between items-center text-sm">
                                                    <span className="text-gray-600">Impuestos:</span>
                                                    <span className="font-medium text-gray-900">${data.tax_amount.toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between items-center text-lg font-bold border-t border-gray-200 pt-3">
                                                    <span className="text-gray-900">Total:</span>
                                                    <span className="text-blue-600">${data.total.toFixed(2)}</span>
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
                                    <p className="text-xs text-gray-500">
                                        Estas notas aparecerán al final de la factura
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
                                <a href="/invoices" className="flex items-center justify-center gap-2">
                                    Cancelar
                                </a>
                            </Button>
                            <Button 
                                type="submit" 
                                size="lg"
                                disabled={processing || !ncf}
                                className={`flex items-center justify-center gap-2 min-w-[160px] ${
                                    processing || !ncf 
                                        ? 'bg-gray-400 hover:bg-gray-400' 
                                        : 'bg-blue-600 hover:bg-blue-700'
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
                                        Guardar factura
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}