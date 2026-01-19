import { Head, router, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Currency } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Cuentas bancarias',
        href: '/bank-accounts',
    },
    {
        title: 'Editar cuenta',
        href: '#',
    },
];

interface Props {
    bankAccount: {
        id: number;
        name: string;
        type: string;
        account_number?: string | null;
        currency_id: number;
        description: string;
        is_active: boolean;
    };
    currencies: Currency[];
    accountTypes: Array<{ value: string; label: string; description: string }>;
}

interface BankAccountFormData {
    name: string;
    type: string;
    currency_id: number | string;
    account_number: string;
    description: string;
    is_active: boolean;
}

export default function EditBankAccount({ bankAccount, currencies, accountTypes }: Props) {
    const { data, setData, patch, processing, errors } = useForm<BankAccountFormData>({
        name: bankAccount.name,
        type: bankAccount.type,
        currency_id: bankAccount.currency_id,
        account_number: bankAccount.account_number || '',
        description: bankAccount.description,
        is_active: bankAccount.is_active,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/bank-accounts/${bankAccount.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Editar cuenta bancaria" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Editar cuenta bancaria</h1>
                    <p className="text-gray-600 dark:text-gray-400">Actualiza la información de la cuenta bancaria.</p>
                </div>

                <form onSubmit={handleSubmit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Información de la cuenta</CardTitle>
                            <CardDescription>Actualiza los detalles de la cuenta bancaria</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="name">
                                        Nombre de la cuenta <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Ej: Cuenta corriente BHD León"
                                        className={errors.name ? 'border-red-500' : ''}
                                    />
                                    {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="type">
                                        Tipo de cuenta <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                                        <SelectTrigger id="type" className={errors.type ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona el tipo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accountTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    <div>
                                                        <div>{type.label}</div>
                                                        <div className="text-xs text-gray-500">{type.description}</div>
                                                    </div>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.type && <p className="text-sm text-red-500">{errors.type}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="currency_id">
                                        Moneda <span className="text-red-500">*</span>
                                    </Label>
                                    <Select value={data.currency_id.toString()} onValueChange={(value) => setData('currency_id', Number(value))}>
                                        <SelectTrigger id="currency_id" className={errors.currency_id ? 'border-red-500' : ''}>
                                            <SelectValue placeholder="Selecciona la moneda" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((currency) => (
                                                <SelectItem key={currency.id} value={currency.id.toString()}>
                                                    {currency.code} - {currency.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.currency_id && <p className="text-sm text-red-500">{errors.currency_id}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="account_number">Número de cuenta</Label>
                                    <Input
                                        id="account_number"
                                        value={data.account_number}
                                        onChange={(e) => setData('account_number', e.target.value)}
                                        placeholder="Ej: 1234-5678-9012"
                                        className={errors.account_number ? 'border-red-500' : ''}
                                    />
                                    {errors.account_number && <p className="text-sm text-red-500">{errors.account_number}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Descripción</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Detalles adicionales sobre esta cuenta..."
                                    rows={3}
                                    className={errors.description ? 'border-red-500' : ''}
                                />
                                {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked as boolean)}
                                />
                                <Label htmlFor="is_active" className="cursor-pointer">
                                    Cuenta activa
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end space-x-4">
                        <Button type="button" variant="outline" onClick={() => router.get('/bank-accounts')} disabled={processing}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
