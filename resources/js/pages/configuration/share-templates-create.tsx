import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import ShareTemplateForm from '@/pages/configuration/share-template-form';
import { type BreadcrumbItem, type ShareVariable } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'Plantillas de compartir', href: '/share-templates' },
    { title: 'Nueva plantilla', href: '/share-templates/create' },
];

interface Props {
    entityOptions: Record<string, string>;
    channelOptions: Record<string, string>;
    variableGroups: Record<string, ShareVariable[]>;
}

export default function ShareTemplatesCreate({ entityOptions, channelOptions, variableGroups }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Plantilla de Compartir" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Nueva Plantilla</h1>
                    <p className="text-gray-600 dark:text-gray-400">Crea una plantilla para el modal de compartir.</p>
                </div>

                <ShareTemplateForm
                    action="/share-templates"
                    method="post"
                    entityOptions={entityOptions}
                    channelOptions={channelOptions}
                    variableGroups={variableGroups}
                />
            </div>
        </AppLayout>
    );
}
