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
    created_at: string;
    updated_at: string;
}

export interface ProductStock {
    id: number;
    product_id: number;
    workspace_id: number;
    quantity: number;
    minimum_quantity: number;
    created_at: string;
    updated_at: string;
}

export interface StockMovement {
    id: number;
    product_id: number;
    workspace_id: number;
    type: 'in' | 'out' | 'adjustment' | 'transfer' | 'initial';
    quantity: number;
    reference_number?: string | null;
    notes?: string | null;
    created_by: number;
    created_at: string;
    updated_at: string;
    created_by_user?: User;
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
    workspace_id: number;
    created_at: string;
    updated_at: string;
    default_tax?: Tax;
    stock_in_current_workspace?: ProductStock;
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
