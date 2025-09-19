import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { useEffect, type ReactNode } from 'react';
import { Toaster } from "@/components/ui/sonner"
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    console.log('Rendering AppLayout');
    const page = usePage<SharedData>();

    useEffect(() => {
        console.log('Flash messages:', page.props.flash);
        if (page.props.flash?.success) {
            toast.success(page.props.flash.success);
        }
        if (page.props.flash?.error) {
            toast.error(page.props.flash.error);
        }
    }, [page.props.flash]);

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            {children}
            <Toaster richColors closeButton/>
        </AppLayoutTemplate>
    );
}
