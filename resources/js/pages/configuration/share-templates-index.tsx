import { Head, Link } from '@inertiajs/react';
import { Share2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ShareTemplate } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'Plantillas de compartir', href: '/share-templates' },
];

interface Props {
    templates: ShareTemplate[];
    entityOptions: Record<string, string>;
    channelOptions: Record<string, string>;
}

export default function ShareTemplatesIndex({ templates, entityOptions, channelOptions }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Plantillas de compartir" />
        
            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Plantillas de compartir</h1>
                        <p className="text-gray-600 dark:text-gray-400">Administra los textos base para correo y WhatsApp.</p>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {templates.map((template) => (
                        <Card key={template.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Share2 className="h-4 w-4" />
                                    {template.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="text-sm text-gray-600">
                                    <strong>Entidad:</strong> {entityOptions[template.entity_type]}
                                </div>
                                <div className="text-sm text-gray-600">
                                    <strong>Canal:</strong> {channelOptions[template.channel]}
                                </div>
                                <div className="text-sm text-gray-600">
                                    <strong>Estado:</strong> {template.is_active ? 'Activa' : 'Inactiva'}
                                </div>
                                <p className="line-clamp-4 text-sm text-gray-600">{template.body}</p>

                                <Button asChild variant="outline" className="w-full">
                                    <Link href={`/share-templates/${template.id}/edit`}>Editar</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
