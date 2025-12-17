import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

import type { SharedData } from '@/types';

// Workspace-scoped permissions (from spatie/laravel-permission roles)
export type WorkspacePermission =
    | 'view products'
    | 'create products'
    | 'edit products'
    | 'delete products'
    | 'view contacts'
    | 'create contacts'
    | 'edit contacts'
    | 'delete contacts'
    | 'view invoices'
    | 'create invoices'
    | 'edit invoices'
    | 'delete invoices'
    | 'view quotations'
    | 'create quotations'
    | 'edit quotations'
    | 'delete quotations'
    | 'view prescriptions'
    | 'create prescriptions'
    | 'edit prescriptions'
    | 'delete prescriptions'
    | 'view inventory'
    | 'adjust inventory'
    | 'transfer inventory'
    | 'view configuration'
    | 'edit configuration'
    | 'view reports'
    | 'export reports';

// Business-level permissions (granted to Owner/Admin users)
export type BusinessPermission =
    | 'view_configuration'
    | 'manage_members'
    | 'manage_taxes'
    | 'manage_invoice_documents'
    | 'manage_bank_accounts'
    | 'manage_currencies'
    | 'manage_business_details';

// Combined permission type
export type Permission = WorkspacePermission | BusinessPermission;

/**
 * Hook to check user permissions for the current workspace.
 *
 * @example
 * const { can, canAny, canAll, permissions } = usePermissions();
 *
 * // Check single permission
 * if (can('edit products')) { ... }
 *
 * // Check if user has any of the permissions
 * if (canAny(['create invoices', 'edit invoices'])) { ... }
 *
 * // Check if user has all permissions
 * if (canAll(['view reports', 'export reports'])) { ... }
 */
export function usePermissions() {
    const { userPermissions } = usePage<SharedData>().props;

    // Ensure permissions is always an array
    const permissionsList = Array.isArray(userPermissions) ? userPermissions : [];

    const permissionsSet = useMemo(() => new Set(permissionsList), [permissionsList]);

    /**
     * Check if the user has a specific permission.
     */
    const can = useCallback(
        (permission: Permission): boolean => {
            return permissionsSet.has(permission);
        },
        [permissionsSet],
    );

    /**
     * Check if the user has any of the specified permissions.
     */
    const canAny = useCallback(
        (permissionsToCheck: Permission[]): boolean => {
            return permissionsToCheck.some((p) => permissionsSet.has(p));
        },
        [permissionsSet],
    );

    /**
     * Check if the user has all of the specified permissions.
     */
    const canAll = useCallback(
        (permissionsToCheck: Permission[]): boolean => {
            return permissionsToCheck.every((p) => permissionsSet.has(p));
        },
        [permissionsSet],
    );

    /**
     * Check if the user does not have a specific permission.
     */
    const cannot = useCallback(
        (permission: Permission): boolean => {
            return !permissionsSet.has(permission);
        },
        [permissionsSet],
    );

    return {
        permissions: permissionsList,
        can,
        canAny,
        canAll,
        cannot,
    };
}
