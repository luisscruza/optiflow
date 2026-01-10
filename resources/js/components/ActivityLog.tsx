import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { ActivityLog as ActivityLogType } from '@/types';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { Clock, User } from 'lucide-react';
import React from 'react';

interface ActivityLogProps {
    activity: ActivityLogType;
    showSubject?: boolean;
    fieldLabels?: Record<string, string>;
}

export const ActivityLog: React.FC<ActivityLogProps> = ({ activity, showSubject = false, fieldLabels = {} }) => {
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
                return 'text-green-600 bg-green-50 border-green-200';
            case 'updated':
                return 'text-blue-600 bg-blue-50 border-blue-200';
            case 'deleted':
                return 'text-red-600 bg-red-50 border-red-200';
            default:
                return 'text-gray-600 bg-gray-50 border-gray-200';
        }
    };

    const getEventIcon = (event: string) => {
        switch (event) {
            case 'created':
                return '✨';
            case 'updated':
                return '📝';
            case 'deleted':
                return '🗑️';
            default:
                return '📋';
        }
    };

    const hasChanges = activity.properties.old && Object.keys(activity.properties.old).length > 0;

    return (
        <div className="group relative rounded-lg border bg-white p-4 transition-shadow hover:shadow-md">
            <div className="flex items-start gap-3">
                {/* Avatar */}
                <div className="flex-shrink-0">
                    {activity.causer ? (
                        <Avatar className="h-9 w-9">
                            <AvatarFallback className="bg-gradient-to-br from-blue-100 to-blue-200 text-sm font-medium text-blue-700">
                                {getInitials(activity.causer.name)}
                            </AvatarFallback>
                        </Avatar>
                    ) : (
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100">
                            <User className="h-4 w-4 text-gray-400" />
                        </div>
                    )}
                </div>

                {/* Content */}
                <div className="min-w-0 flex-1">
                    {/* Header */}
                    <div className="mb-1 flex flex-wrap items-center gap-2">
                        <span className="font-medium text-gray-900">{activity.causer?.name ?? 'Sistema'}</span>
                        <span
                            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${getEventColor(activity.event)}`}
                        >
                            <span className="mr-1">{getEventIcon(activity.event)}</span>
                            {activity.description}
                        </span>
                        {showSubject && (
                            <span className="text-xs text-gray-500">
                                en {activity.subject_type.split('\\').pop()} #{activity.subject_id}
                            </span>
                        )}
                    </div>

                    {/* Timestamp */}
                    <div className="mb-2 flex items-center gap-1 text-xs text-gray-500">
                        <Clock className="h-3 w-3" />
                        {formatTimeAgo(activity.created_at)}
                    </div>

                    {/* Changes */}
                    {hasChanges && (
                        <div className="mt-3 space-y-2 rounded-md bg-gray-50 p-3">
                            <div className="text-xs font-semibold tracking-wide text-gray-700 uppercase">Cambios realizados</div>
                            {Object.entries(activity.properties.old).map(([key, oldValue]) => {
                                const newValue = activity.properties.attributes?.[key];
                                const label = fieldLabels[key] || key.replace(/_/g, ' ');
                                return (
                                    <div key={key} className="flex items-start gap-2 text-sm">
                                        <span className="min-w-[120px] font-medium text-gray-600">{label}:</span>
                                        <div className="flex flex-1 items-center gap-2">
                                            <span className="rounded bg-red-100 px-2 py-0.5 text-red-700 line-through">
                                                {String(oldValue || '—')}
                                            </span>
                                            <span className="text-gray-400">→</span>
                                            <span className="rounded bg-green-100 px-2 py-0.5 text-green-700">{String(newValue || '—')}</span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
