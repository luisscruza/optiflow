import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ActivityLog as ActivityLogType } from '@/types';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { Clock, History } from 'lucide-react';
import React from 'react';

interface ActivityLogTimelineProps {
    activities: ActivityLogType[];
    fieldLabels?: Record<string, string>;
    title?: string;
    description?: string;
}

export const ActivityLogTimeline: React.FC<ActivityLogTimelineProps> = ({
    activities,
    fieldLabels = {},
    title = 'Historial de cambios',
    description = 'Registro detallado de todas las modificaciones realizadas a esta factura',
}) => {
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatTimeAgo = (dateString: string) => {
        try {
            return formatDistanceToNow(new Date(dateString), {
                addSuffix: true,
                locale: es,
            });
        } catch {
            return 'hace un momento';
        }
    };

    const getEventColor = (event: string) => {
        switch (event) {
            case 'created':
                return 'text-green-600';
            case 'updated':
                return 'text-blue-600';
            case 'deleted':
                return 'text-red-600';
            default:
                return 'text-gray-600';
        }
    };

    const getEventDotColor = (event: string) => {
        switch (event) {
            case 'created':
                return 'bg-green-500';
            case 'updated':
                return 'bg-blue-500';
            case 'deleted':
                return 'bg-red-500';
            default:
                return 'bg-gray-500';
        }
    };

    const formatValue = (value: unknown): string => {
        if (value === null || value === undefined || value === '') return '—';
        if (typeof value === 'number') {
            // Format numbers nicely
            return new Intl.NumberFormat('es-ES', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(value);
        }
        return String(value);
    };

    const renderChanges = (activity: ActivityLogType) => {
        const hasOldValues = activity.properties.old && Object.keys(activity.properties.old).length > 0;
        const hasNewValues = activity.properties.attributes && Object.keys(activity.properties.attributes).length > 0;

        // For create events without old values, show new values
        if (!hasOldValues && hasNewValues && activity.event === 'created') {
            const entries = Object.entries(activity.properties.attributes);
            if (entries.length === 0) return null;

            return (
                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                    {entries.slice(0, 3).map(([key, value]) => {
                        const label = fieldLabels[key] || key.replace(/_/g, ' ');
                        return (
                            <span key={key}>
                                <span className="text-gray-500">{label}:</span>{' '}
                                <span className="font-medium text-gray-900">{formatValue(value)}</span>
                            </span>
                        );
                    })}
                </div>
            );
        }

        // For updates, show changes inline
        if (hasOldValues) {
            const entries = Object.entries(activity.properties.old);
            return (
                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    {entries.map(([key, oldValue]) => {
                        const newValue = activity.properties.attributes?.[key];
                        const label = fieldLabels[key] || key.replace(/_/g, ' ');
                        return (
                            <span key={key} className="inline-flex items-center gap-1">
                                <span className="text-gray-500">{label}:</span>{' '}
                                <span className="text-red-600 line-through">{formatValue(oldValue)}</span>
                                <span className="text-gray-400">→</span>
                                <span className="font-medium text-green-600">{formatValue(newValue)}</span>
                            </span>
                        );
                    })}
                </div>
            );
        }

        return null;
    };

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-base">
                    <History className="h-4 w-4" />
                    {title}
                </CardTitle>
                {description && <CardDescription className="text-xs">{description}</CardDescription>}
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <div className="mb-2 rounded-full bg-gray-100 p-2">
                            <History className="h-5 w-5 text-gray-400" />
                        </div>
                        <p className="text-sm text-gray-500">No hay actividad registrada.</p>
                    </div>
                ) : (
                    <div className="relative space-y-0">
                        {/* Timeline line */}
                        <div className="absolute top-2 bottom-2 left-[11px] w-0.5 bg-gray-200"></div>

                        {activities.map((activity, index) => {
                            const dotColor = getEventDotColor(activity.event);

                            return (
                                <div key={activity.id} className="relative flex gap-3 pb-4 last:pb-0">
                                    {/* Timeline dot */}
                                    <div className={`relative z-10 mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${dotColor} ring-2 ring-white`}></div>

                                    {/* Content */}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                            <span className="text-sm font-medium text-gray-900">{activity.causer?.name ?? 'Sistema'}</span>
                                            <span className={`text-sm ${getEventColor(activity.event)}`}>{activity.description}</span>
                                            <span className="flex items-center gap-0.5 text-xs text-gray-400">
                                                <Clock className="h-3 w-3" />
                                                {formatTimeAgo(activity.created_at)}
                                            </span>
                                        </div>
                                        {renderChanges(activity)}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};
