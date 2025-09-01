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
