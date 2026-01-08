import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Salesman, type User } from '@/types';

interface Props {
    salesman: Salesman & { user?: User | null };
    users: User[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Configuración', href: '/configuration' },
    { title: 'Vendedores', href: '/salesmen' },
    { title: 'Editar', href: '#' },
];

export default function EditSalesman({ salesman, users }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        name: salesman.name,
        surname: salesman.surname,
        user_id: salesman.user_id || (null as number | null),
    });

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/salesmen/${salesman.id}`);
    };

    const userOptions = users.map((user) => ({
        value: user.id.toString(),
        label: `${user.name} (${user.email})`,
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar vendedor - ${salesman.full_name}`} />

            <div className="space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <div className="flex items-center gap-4">
                    <Button variant="outline" size="sm" onClick={() => window.history.back()}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Volver
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Editar vendedor</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Juan"
                                        className={errors.name ? 'border-red-300' : ''}
                                    />
                                    {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="surname">
                                        Apellido <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="surname"
                                        value={data.surname}
                                        onChange={(e) => setData('surname', e.target.value)}
                                        placeholder="Pérez"
                                        className={errors.surname ? 'border-red-300' : ''}
                                    />
                                    {errors.surname && <p className="text-sm text-red-600">{errors.surname}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="user_id">Usuario vinculado (opcional)</Label>
                                <SearchableSelect
                                    options={userOptions}
                                    value={data.user_id?.toString() || ''}
                                    onValueChange={(value) => setData('user_id', value ? parseInt(value) : null)}
                                    placeholder="Seleccionar usuario..."
                                    searchPlaceholder="Buscar usuario..."
                                    emptyText="No se encontró ningún usuario."
                                />
                                {errors.user_id && <p className="text-sm text-red-600">{errors.user_id}</p>}
                                <p className="text-sm text-gray-500">
                                    Puedes vincular este vendedor a un usuario del sistema para control de acceso.
                                </p>
                            </div>

                            <div className="flex justify-end gap-3">
                                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    Actualizar vendedor
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
