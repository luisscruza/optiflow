import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, MessageCircle, Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'WhatsApp Business', href: '/whatsapp-accounts' },
    { title: 'Agregar', href: '/whatsapp-accounts/create' },
];

export default function WhatsappAccountsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone_number_id: '',
        business_account_id: '',
        access_token: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/whatsapp-accounts');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agregar cuenta de WhatsApp" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <MessageCircle className="h-6 w-6 text-green-600" />
                            Agregar cuenta de WhatsApp
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Configura una nueva cuenta para usar en tus automatizaciones.</p>
                    </div>

                    <Link href="/whatsapp-accounts">
                        <Button variant="outline">
                            <ChevronLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <div className="mx-auto max-w-2xl">
                    <div className="rounded-lg border bg-card p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="rounded-md border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                                <h3 className="font-medium text-green-900 dark:text-green-100">📱 ¿Cómo obtener las credenciales?</h3>
                                <ol className="mt-2 list-inside list-decimal space-y-1 text-sm text-green-800 dark:text-green-200">
                                    <li>
                                        Ve a{' '}
                                        <a
                                            href="https://developers.facebook.com/apps"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="underline"
                                        >
                                            Meta for Developers
                                        </a>
                                    </li>
                                    <li>Selecciona tu app y ve a WhatsApp → API Setup</li>
                                    <li>Copia el Phone Number ID y el Access Token</li>
                                    <li>Para plantillas, también necesitas el Business Account ID</li>
                                </ol>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre de la cuenta</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="WhatsApp Principal"
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                <p className="text-xs text-muted-foreground">Un nombre descriptivo para identificar esta cuenta.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phone_number_id">Phone Number ID</Label>
                                <Input
                                    id="phone_number_id"
                                    value={data.phone_number_id}
                                    onChange={(e) => setData('phone_number_id', e.target.value)}
                                    placeholder="123456789012345"
                                />
                                {errors.phone_number_id && <p className="text-sm text-destructive">{errors.phone_number_id}</p>}
                                <p className="text-xs text-muted-foreground">El ID del número de teléfono registrado en WhatsApp Business API.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="business_account_id">Business Account ID (opcional)</Label>
                                <Input
                                    id="business_account_id"
                                    value={data.business_account_id}
                                    onChange={(e) => setData('business_account_id', e.target.value)}
                                    placeholder="123456789012345"
                                />
                                {errors.business_account_id && <p className="text-sm text-destructive">{errors.business_account_id}</p>}
                                <p className="text-xs text-muted-foreground">
                                    Requerido para cargar plantillas de mensajes. Lo encuentras en WhatsApp Manager.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="access_token">Access Token</Label>
                                <Input
                                    id="access_token"
                                    type="password"
                                    value={data.access_token}
                                    onChange={(e) => setData('access_token', e.target.value)}
                                    placeholder="EAABcd..."
                                />
                                {errors.access_token && <p className="text-sm text-destructive">{errors.access_token}</p>}
                                <p className="text-xs text-muted-foreground">
                                    El token de acceso permanente. Se recomienda usar un System User Token para acceso sin expiración.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Link href="/whatsapp-accounts">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Guardando...' : 'Guardar cuenta'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
