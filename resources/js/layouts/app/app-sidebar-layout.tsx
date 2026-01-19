import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { gitVersion } = usePage().props as { gitVersion: string };
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div className='min-h-[90vh]'>
                {children}
                </div>
                {/* Footer */}
                <div className="w-full flex items-center px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    &copy; {new Date().getFullYear()} Optiflow — <span className="ml-2">build {gitVersion}</span>
                </div>
            </AppContent>
        </AppShell>
    );
}
