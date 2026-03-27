import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import ShareTemplateForm from '@/pages/configuration/share-template-form';
import { type BreadcrumbItem, type ShareTemplate, type ShareVariable } from '@/types';

interface Props {
    template: ShareTemplate;
    entityOptions: Record<string, string>;
    channelOptions: Record<string, string>;
    variableGroups: Record<string, ShareVariable[]>;
}

export default function ShareTemplatesEdit({ template, entityOptions, channelOptions, variableGroups }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Configuración', href: '/configuration' },
        { title: 'Plantillas de compartir', href: '/share-templates' },
        { title: template.name, href: `/share-templates/${template.id}/edit` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${template.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Editar Plantilla</h1>
                    <p className="text-gray-600 dark:text-gray-400">Actualiza el contenido que se usa para compartir documentos.</p>
                </div>

                <ShareTemplateForm
                    action={`/share-templates/${template.id}`}
                    method="put"
                    entityOptions={entityOptions}
                    channelOptions={channelOptions}
                    variableGroups={variableGroups}
                    template={template}
                />
            </div>
        </AppLayout>
    );
}
