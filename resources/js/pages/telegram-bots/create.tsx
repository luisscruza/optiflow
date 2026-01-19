import { Head, Link, useForm } from '@inertiajs/react';
import { Bot, ChevronLeft, Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bots de Telegram', href: '/telegram-bots' },
    { title: 'Agregar', href: '/telegram-bots/create' },
];

export default function TelegramBotsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        bot_token: '',
        default_chat_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/telegram-bots');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Agregar bot de Telegram" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <Bot className="h-6 w-6" />
                            Agregar bot de Telegram
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Configura un nuevo bot para usar en tus automatizaciones.</p>
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
                            <div className="rounded-md border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-950">
                                <h3 className="font-medium text-sky-900 dark:text-sky-100">💡 ¿Cómo obtener el Bot Token?</h3>
                                <ol className="mt-2 list-inside list-decimal space-y-1 text-sm text-sky-800 dark:text-sky-200">
                                    <li>Abre Telegram y busca a @BotFather</li>
                                    <li>Envía el comando /newbot</li>
                                    <li>Sigue las instrucciones para crear tu bot</li>
                                    <li>Copia el token que te proporciona</li>
                                </ol>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre del bot</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Mi bot de notificaciones"
                                />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                <p className="text-xs text-muted-foreground">Un nombre descriptivo para identificar este bot.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="bot_token">Bot Token</Label>
                                <Input
                                    id="bot_token"
                                    type="password"
                                    value={data.bot_token}
                                    onChange={(e) => setData('bot_token', e.target.value)}
                                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                                />
                                {errors.bot_token && <p className="text-sm text-destructive">{errors.bot_token}</p>}
                                <p className="text-xs text-muted-foreground">El token único que te proporcionó @BotFather.</p>
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
                                <p className="text-xs text-muted-foreground">
                                    El ID del chat, grupo o canal donde enviar mensajes por defecto. Puedes usar @userinfobot para obtener tu Chat ID.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Link href="/telegram-bots">
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {processing ? 'Guardando...' : 'Guardar bot'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
