import { Head, Link, useForm } from '@inertiajs/react';
import { Bot, ChevronLeft, Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TelegramBot {
    id: string;
    name: string;
    bot_username: string | null;
    default_chat_id: string | null;
    is_active: boolean;
}

interface Props {
    bot: TelegramBot;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bots de Telegram', href: '/telegram-bots' },
    { title: 'Editar', href: '#' },
];

export default function TelegramBotsEdit({ bot }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: bot.name,
        bot_token: '',
        default_chat_id: bot.default_chat_id ?? '',
        is_active: bot.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/telegram-bots/${bot.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar bot - ${bot.name}`} />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <Bot className="h-6 w-6" />
                            Editar bot de Telegram
                        </h1>
                        {bot.bot_username && <p className="mt-1 text-sm text-sky-600">@{bot.bot_username}</p>}
                    </div>

                    <Link href="/telegram-bots">
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
                                <Label htmlFor="name">Nombre del bot</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Mi bot de notificaciones"
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="bot_token">Bot Token (dejar vacío para mantener el actual)</Label>
                                <Input
                                    id="bot_token"
                                    type="password"
                                    value={data.bot_token}
                                    onChange={(e) => setData('bot_token', e.target.value)}
                                    placeholder="••••••••••••••••••••"
                                />
                                {errors.bot_token && <p className="text-sm text-destructive">{errors.bot_token}</p>}
                                <p className="text-xs text-muted-foreground">Solo ingresa un nuevo token si deseas cambiarlo.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="default_chat_id">Chat ID predeterminado (opcional)</Label>
                                <Input
                                    id="default_chat_id"
                                    value={data.default_chat_id}
                                    onChange={(e) => setData('default_chat_id', e.target.value)}
                                    placeholder="-1001234567890"
                                />
                                {errors.default_chat_id && <p className="text-sm text-destructive">{errors.default_chat_id}</p>}
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300"
                                />
                                <Label htmlFor="is_active" className="cursor-pointer font-normal">
                                    Bot activo
                                </Label>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Link href="/telegram-bots">
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
