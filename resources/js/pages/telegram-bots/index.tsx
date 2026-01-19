import { Head, Link, router } from '@inertiajs/react';
import { Bot, CheckCircle, Edit, Plus, Trash2, XCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface TelegramBot {
    id: string;
    name: string;
    bot_username: string | null;
    default_chat_id: string | null;
    is_active: boolean;
    created_at: string;
}

interface Props {
    bots: TelegramBot[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Bots de Telegram', href: '/telegram-bots' }];

export default function TelegramBotsIndex({ bots }: Props) {
    const handleDelete = (id: string) => {
        if (confirm('¿Estás seguro de eliminar este bot?')) {
            router.delete(`/telegram-bots/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bots de Telegram" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <Bot className="h-6 w-6" />
                            Bots de Telegram
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">Configura tus bots de Telegram para usarlos en automatizaciones.</p>
                    </div>

                    <Link href="/telegram-bots/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar bot
                        </Button>
                    </Link>
                </div>

                {bots.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-12 text-center">
                        <Bot className="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 className="mt-4 text-lg font-medium">No hay bots configurados</h3>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Agrega tu primer bot de Telegram para comenzar a enviar mensajes automáticos.
                        </p>
                        <Link href="/telegram-bots/create" className="mt-4 inline-block">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar bot
                            </Button>
                        </Link>
                    </div>
                ) : (
                    <div className="rounded-lg border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Usuario del bot</TableHead>
                                    <TableHead>Chat ID predeterminado</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {bots.map((bot) => (
                                    <TableRow key={bot.id}>
                                        <TableCell className="font-medium">{bot.name}</TableCell>
                                        <TableCell>
                                            {bot.bot_username ? (
                                                <span className="text-sky-600">@{bot.bot_username}</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {bot.default_chat_id ? (
                                                <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{bot.default_chat_id}</code>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {bot.is_active ? (
                                                <span className="inline-flex items-center gap-1 text-green-600">
                                                    <CheckCircle className="h-4 w-4" />
                                                    Activo
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 text-muted-foreground">
                                                    <XCircle className="h-4 w-4" />
                                                    Inactivo
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link href={`/telegram-bots/${bot.id}/edit`}>
                                                    <Button size="sm" variant="ghost">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() => handleDelete(bot.id)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
