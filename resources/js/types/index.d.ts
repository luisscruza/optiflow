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

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    workspace?: WorkspaceData;
    sidebarOpen: boolean;
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
