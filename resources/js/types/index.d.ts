import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Currency {
    id: number;
    name: string;
    code: string;
    symbol: string;
    is_default: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    workspace?: WorkspaceData;
    sidebarOpen: boolean;
    companyDetails: {
        company_name: string;
        address?: string | null;
        phone?: string | null;
        email?: string | null;
        tax_id?: string | null;
        currency?: string | null;
        logo?: string | null;
    };
    defaultCurrency?: Currency | null;
    newlyCreatedContact?: Contact | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    current_workspace_id?: number | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Workspace {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    owner_id: number;
    settings?: Record<string, unknown> | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    owner?: User;
    users?: User[];
    users_count?: number;
    is_owner?: boolean;
    pivot?: {
        role: string;
        joined_at: string;
    };
}

export interface WorkspaceData {
    current: Workspace | null;
    available: Workspace[];
}

export interface Tax {
    id: number;
    name: string;
    rate: number;
    is_default: boolean;
    created_at: string;
    updated_at: string;
    products_count?: number;
    document_items_count?: number;
}

export interface PaginatedTaxes {
    data: Tax[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface TaxFilters {
    search?: string;
}

export interface ProductStock {
    id: number;
    product_id: number;
    workspace_id: number;
    quantity: number;
    minimum_quantity: number;
    created_at: string;
    updated_at: string;
    product?: Product;
}

export interface StockMovement {
    id: number;
    product_id: number;
    workspace_id: number;
    from_workspace_id?: number | null;
    to_workspace_id?: number | null;
    type: 'in' | 'out' | 'adjustment' | 'transfer' | 'initial' | 'transfer_in' | 'transfer_out' | 'set_quantity' | 'add_quantity' | 'remove_quantity';
    quantity: number;
    reference_number?: string | null;
    notes?: string | null;
    unit_cost?: number | null;
    created_at: string;
    updated_at: string;
    created_by?: User;
    product?: Product;
    from_workspace?: Workspace;
    to_workspace?: Workspace;
}

export interface StockAdjustment {
    id: number;
    product_id: number;
    workspace_id: number;
    adjustment_type: 'set_quantity' | 'add_quantity' | 'remove_quantity';
    quantity: number;
    previous_quantity?: number;
    new_quantity?: number;
    note?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    product?: Product;
    created_by?: User;
}

export interface StockTransfer {
    id: number;
    product_id: number;
    from_workspace_id: number;
    to_workspace_id: number;
    quantity: number;
    note?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    product?: Product;
    from_workspace?: Workspace;
    to_workspace?: Workspace;
    created_by?: User;
}

export interface PaginatedStockMovements {
    data: StockMovement[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface PaginatedStockAdjustments {
    data: ProductStock[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface Product {
    id: number;
    name: string;
    sku: string;
    description?: string | null;
    price: number;
    cost?: number | null;
    track_stock: boolean;
    default_tax_id?: number | null;
    created_at: string;
    updated_at: string;
    default_tax?: Tax;
    stock_in_current_workspace?: ProductStock;
    stocks?: ProductStock[];
    stock_movements?: StockMovement[];
    current_stock?: ProductStock;
    stock_quantity?: number;
    minimum_quantity?: number;
    stock_status?: 'not_tracked' | 'out_of_stock' | 'low_stock' | 'in_stock';
}

export interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface ProductFilters {
    search?: string;
    track_stock?: boolean;
    low_stock?: boolean;
}

export interface Address {
    id: number;
    contact_id: number;
    type: string;
    province?: string | null;
    municipality?: string | null;
    country?: string | null;
    description?: string | null;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
    full_address?: string | null;
}

export interface Contact {
    id: number;
    workspace_id: number;
    name: string;
    identification_type?: string | null;
    identification_number?: string | null;
    email?: string | null;
    phone_primary?: string | null;
    phone_secondary?: string | null;
    mobile?: string | null;
    fax?: string | null;
    contact_type: 'customer' | 'supplier';
    status: 'active' | 'inactive';
    observations?: string | null;
    credit_limit: number;
    created_at: string;
    updated_at: string;
    addresses?: Address[];
    primary_address?: Address | null;
    identification_object?: {
        type: string;
        number: string;
    } | null;
    documents_count?: number;
    supplied_stocks_count?: number;
}

export interface PaginatedContacts {
    data: Contact[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface ContactFilters {
    search?: string;
}

export interface IdentificationTypeOption {
    value: string;
    label: string;
}

export type ContactType = 'customer' | 'supplier';
export type IdentificationType = 'cedula' | 'pasaporte' | 'rnc' | 'nif' | 'nie';

export type ContactType = 'customer' | 'supplier';
export type IdentificationType = 'cedula' | 'nit' | 'passport' | 'rut' | 'other';

export interface DocumentSubtype {
    id: number;
    name: string;
    prefix: string;
    next_number: number;
    is_default: boolean;
    valid_until_date?: string | null;
    start_number: number;
    end_number: number;
    created_at: string;
    updated_at: string;
}

export interface Document {
    id: number;
    workspace_id: number;
    contact_id: number;
    type: 'invoice' | 'quote' | 'receipt';
    document_subtype_id: number;
    status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
    document_number: string;
    issue_date: string;
    due_date: string;
    total_amount: number;
    subtotal_amount: number;
    tax_amount: number;
    discount_amount: number;
    notes?: string | null;
    created_by: number;
    currency_id: number;
    created_at: string;
    updated_at: string;
    contact: Contact;
    document_subtype: DocumentSubtype;
    created_by_user?: User;
}

export interface PaginatedDocuments {
    data: Document[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
    links: {
        first?: string;
        last?: string;
        prev?: string;
        next?: string;
    };
}

export interface DocumentFilters {
    search?: string;
    status?: string;
}
