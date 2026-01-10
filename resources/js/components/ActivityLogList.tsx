import { ActivityLog } from '@/components/ActivityLog';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ActivityLog as ActivityLogType } from '@/types';
import { History } from 'lucide-react';
import React from 'react';

interface ActivityLogListProps {
    activities: ActivityLogType[];
    fieldLabels: Record<string, string>;
    title?: string;
    description?: string;
    showSubject?: boolean;
    emptyMessage?: string;
}

export const ActivityLogList: React.FC<ActivityLogListProps> = ({
    activities,
    fieldLabels,
    title = 'Historial de cambios',
    description = 'Registro detallado de todas las modificaciones realizadas',
    showSubject = false,
    emptyMessage = 'No hay actividad registrada.',
}) => {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <History className="h-5 w-5" />
                    {title}
                </CardTitle>
                {description && <CardDescription>{description}</CardDescription>}
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="mb-3 rounded-full bg-gray-100 p-3">
                            <History className="h-6 w-6 text-gray-400" />
                        </div>
                        <p className="text-sm text-gray-500">{emptyMessage}</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {activities.map((activity) => (
                            <ActivityLog key={activity.id} activity={activity} showSubject={showSubject} fieldLabels={fieldLabels} />
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
};
