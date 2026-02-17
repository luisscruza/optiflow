import { Toaster } from '@/components/ui/sonner';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';
import { toast } from 'sonner';

type FlashAction = {
    label: string;
    href: string;
};

type FlashPayload = string | { message: string; action?: FlashAction };

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    const page = usePage<SharedData>();

    useEffect(() => {
        const notify = (payload: FlashPayload | undefined, type: 'success' | 'error', duration?: number) => {
            if (!payload) {
                return;
            }

            const normalized = typeof payload === 'string' ? { message: payload } : payload;

            const id = toast[type](normalized.message, {
                duration,
                action: normalized.action
                    ? {
                          label: normalized.action.label,
                          onClick() {
                              window.open(normalized.action?.href ?? '/', '_blank', 'noopener,noreferrer');
                              toast.dismiss(id);
                          },
                      }
                    : undefined,
            });
        };

        notify(page.props.flash?.success, 'success');
        notify(page.props.flash?.error, 'error');
        notify(page.props.flash?.persistentError, 'error', 9999999);
    }, [page.props.flash]);

    return (
        <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
            {children}
            <Toaster richColors closeButton />
        </AppLayoutTemplate>
    );
};
