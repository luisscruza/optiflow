import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Edit, MessageCircle, Plus, Trash2, XCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface WhatsappAccount {
    id: string;
    name: string;
    phone_number_id: string;
    display_phone_number: string | null;
    business_account_id: string | null;
    is_active: boolean;
    created_at: string;
}

interface Props {
    accounts: WhatsappAccount[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'WhatsApp Business', href: '/whatsapp-accounts' },
];

export default function WhatsappAccountsIndex({ accounts }: Props) {
    const handleDelete = (id: string) => {
        if (confirm('¿Estás seguro de eliminar esta cuenta?')) {
            router.delete(`/whatsapp-accounts/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="WhatsApp Business" />

            <div className="px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            <MessageCircle className="h-6 w-6 text-green-600" />
                            WhatsApp Business
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Configura tus cuentas de WhatsApp Business Cloud API para usarlas en automatizaciones.
                        </p>
                    </div>

                    <Link href="/whatsapp-accounts/create">
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar cuenta
                        </Button>
                    </Link>
                </div>

                {accounts.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-12 text-center">
                        <MessageCircle className="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 className="mt-4 text-lg font-medium">No hay cuentas configuradas</h3>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Agrega tu primera cuenta de WhatsApp Business para comenzar a enviar mensajes automáticos.
                        </p>
                        <Link href="/whatsapp-accounts/create" className="mt-4 inline-block">
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar cuenta
                            </Button>
                        </Link>
                    </div>
                ) : (
                    <div className="rounded-lg border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Número</TableHead>
                                    <TableHead>Phone Number ID</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {accounts.map((account) => (
                                    <TableRow key={account.id}>
                                        <TableCell className="font-medium">{account.name}</TableCell>
                                        <TableCell>
                                            {account.display_phone_number ? (
                                                <span className="text-green-600">{account.display_phone_number}</span>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{account.phone_number_id}</code>
                                        </TableCell>
                                        <TableCell>
                                            {account.is_active ? (
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
                                                <Link href={`/whatsapp-accounts/${account.id}/edit`}>
                                                    <Button size="sm" variant="ghost">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="text-destructive hover:text-destructive"
                                                    onClick={() => handleDelete(account.id)}
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
