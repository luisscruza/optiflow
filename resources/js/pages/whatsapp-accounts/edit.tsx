import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronLeft, MessageCircle, Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface WhatsappAccount {
    id: string;
    name: string;
    phone_number_id: string;
    display_phone_number: string | null;
    business_account_id: string | null;
    is_active: boolean;
}

interface Props {
    account: WhatsappAccount;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'WhatsApp Business', href: '/whatsapp-accounts' },
    { title: 'Editar', href: '#' },
];

export default function WhatsappAccountsEdit({ account }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: account.name,
        phone_number_id: account.phone_number_id,
        business_account_id: account.business_account_id ?? '',
        access_token: '',
        is_active: account.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/whatsapp-accounts/${account.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${account.name}`} />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <MessageCircle className="h-6 w-6 text-green-600" />
                            Editar cuenta de WhatsApp
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {account.display_phone_number && <span className="text-green-600">{account.display_phone_number}</span>}
                        </p>
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
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre de la cuenta</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="WhatsApp Principal"
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
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
                                <p className="text-xs text-muted-foreground">Requerido para cargar plantillas de mensajes.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="access_token">Access Token (dejar vacío para mantener)</Label>
                                <Input
                                    id="access_token"
                                    type="password"
                                    value={data.access_token}
                                    onChange={(e) => setData('access_token', e.target.value)}
                                    placeholder="••••••••••••"
                                />
                                {errors.access_token && <p className="text-sm text-destructive">{errors.access_token}</p>}
                                <p className="text-xs text-muted-foreground">Solo completa si deseas cambiar el token actual.</p>
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', Boolean(checked))}
                                />
                                <div>
                                    <Label htmlFor="is_active" className="cursor-pointer">
                                        Cuenta activa
                                    </Label>
                                    <p className="text-xs text-muted-foreground">Si está inactiva, no se podrá usar en automatizaciones.</p>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Link href="/whatsapp-accounts">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Guardando...' : 'Guardar cambios'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
