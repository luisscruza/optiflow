import { Head, Link, router } from '@inertiajs/react';
import { Bell, Check, CheckCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type PaginatedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notificaciones',
        href: '/notifications',
    },
];

interface Notification {
    id: string;
    type: string;
    data: {
        message: string;
        comment_id?: string;
        mentioner_name?: string;
        comment_text?: string;
        [key: string]: any;
    };
    read_at: string | null;
    created_at: string;
}

interface Props {
    notifications: PaginatedData<Notification> & {
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filter: 'all' | 'unread';
}

export default function NotificationsIndex({ notifications, filter }: Props) {
    const [activeTab, setActiveTab] = useState<string>(filter);

    const handleTabChange = (value: string) => {
        setActiveTab(value);
        router.get(
            '/notifications',
            { filter: value },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleMarkAsRead = (id: string) => {
        router.patch(
            `/notifications/${id}/read`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleMarkAllAsRead = () => {
        router.post(
            '/notifications/read-all',
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleDelete = (id: string) => {
        router.delete(`/notifications/${id}`, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const formatDate = (date: string) => {
        const d = new Date(date);
        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Justo ahora';
        if (diffMins < 60) return `Hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;

        return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
    };

    const removeMentionBrackets = (text: string) => {
        // Replace @[Name] with @Name
        return text.replace(/@\[([^\]]+)\]/g, '@$1');
    };

    const getNotificationLink = (notification: Notification) => {
        const { commentable_type, commentable_id } = notification.data;

        if (!commentable_type || !commentable_id) {
            return null;
        }

        // Map commentable types to routes
        const routeMap: Record<string, string> = {
            contact: '/contacts',
            task: '/tasks',
            project: '/projects',
            contact_import: '/contact-imports',
            // Add more as needed
        };

        const baseRoute = routeMap[commentable_type.toLowerCase()];
        return baseRoute ? `${baseRoute}/${commentable_id}` : null;
    };

    const handleNotificationClick = (notification: Notification) => {
        const link = getNotificationLink(notification);

        if (link) {
            // Mark as read if unread
            if (!notification.read_at) {
                handleMarkAsRead(notification.id);
            }

            // Navigate to the link
            router.visit(link);
        }
    };

    const unreadCount = notifications.data.filter((n) => !n.read_at).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notificaciones" />

            <div className="max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Notificaciones</h1>
                        <p className="text-gray-600 dark:text-gray-400">Revisa tus notificaciones y menciones.</p>
                    </div>

                    {unreadCount > 0 && (
                        <Button onClick={handleMarkAllAsRead} variant="outline" size="sm">
                            <CheckCheck className="mr-2 h-4 w-4" />
                            Marcar todas como leídas
                        </Button>
                    )}
                </div>

                <Tabs value={activeTab} onValueChange={handleTabChange}>
                    <TabsList className="mb-4 grid w-full grid-cols-2">
                        <TabsTrigger value="all">Todas</TabsTrigger>
                        <TabsTrigger value="unread">
                            No leídas{' '}
                            {unreadCount > 0 && (
                                <span className="ml-2 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">{unreadCount}</span>
                            )}
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value={activeTab}>
                        {notifications.data.length === 0 ? (
                            <Card>
                                <CardContent className="py-12 text-center">
                                    <Bell className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                    <p className="text-lg font-medium text-gray-900 dark:text-white">No hay notificaciones</p>
                                    <p className="text-gray-500 dark:text-gray-400">
                                        {activeTab === 'unread' ? 'No tienes notificaciones sin leer.' : 'Aún no has recibido ninguna notificación.'}
                                    </p>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="space-y-2">
                                {notifications.data.map((notification) => {
                                    const notificationLink = getNotificationLink(notification);
                                    const cleanCommentText = notification.data.comment_text
                                        ? removeMentionBrackets(notification.data.comment_text)
                                        : null;

                                    return (
                                        <Card
                                            key={notification.id}
                                            className={cn(
                                                'transition-colors',
                                                notificationLink && 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900/50',
                                                !notification.read_at && 'border-l-4 border-l-primary bg-blue-50/50 dark:bg-blue-950/20',
                                            )}
                                            onClick={() => notificationLink && handleNotificationClick(notification)}
                                        >
                                            <CardHeader className="pb-3">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div className="flex-1 space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            {!notification.read_at && <div className="h-2 w-2 rounded-full bg-primary" />}
                                                            <CardTitle className="text-base font-medium">{notification.data.message}</CardTitle>
                                                        </div>
                                                        {cleanCommentText && (
                                                            <CardDescription className="text-sm">{cleanCommentText}</CardDescription>
                                                        )}
                                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                                            {formatDate(notification.created_at)}
                                                        </p>
                                                    </div>

                                                    <div className="flex gap-1" onClick={(e) => e.stopPropagation()}>
                                                        {!notification.read_at && (
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => handleMarkAsRead(notification.id)}
                                                                title="Marcar como leída"
                                                                className="h-8 w-8"
                                                            >
                                                                <Check className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleDelete(notification.id)}
                                                            title="Eliminar"
                                                            className="h-8 w-8 text-red-600 hover:text-red-700 dark:text-red-500"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </CardHeader>
                                        </Card>
                                    );
                                })}
                            </div>
                        )}

                        {/* Pagination */}
                        {notifications.links && notifications.links.length > 3 && (
                            <div className="mt-6 flex items-center justify-center gap-2">
                                {notifications.links.map((link, index) => {
                                    if (!link.url) {
                                        return (
                                            <span
                                                key={index}
                                                className="rounded-md px-3 py-2 text-sm text-gray-400"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        );
                                    }

                                    return (
                                        <Link
                                            key={index}
                                            href={link.url}
                                            className={cn(
                                                'rounded-md px-3 py-2 text-sm transition-colors',
                                                link.active
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                                            )}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
