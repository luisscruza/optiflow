import { Head, router, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Currency } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cuentas bancarias',
        href: '/bank-accounts',
    },
    {
        title: 'Crear cuenta',
        href: '/bank-accounts/create',
    },
];

interface Props {
    currencies: Currency[];
    accountTypes: Array<{ value: string; label: string; description: string }>;
}

interface BankAccountFormData {
    name: string;
    type: string;
    currency_id: number | string;
    account_number: string;
    initial_balance: number | string;
    initial_balance_date: string;
    description: string;
}

export default function CreateBankAccount({ currencies, accountTypes }: Props) {
    const { data, setData, post, processing, errors } = useForm<BankAccountFormData>({
        name: '',
        type: '',
        currency_id: currencies.find((c) => c.is_default)?.id || '',
        account_number: '',
        initial_balance: 0,
        initial_balance_date: new Date().toISOString().split('T')[0],
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/bank-accounts');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear cuenta bancaria" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Crear cuenta bancaria</h1>
                    <p className="text-gray-600 dark:text-gray-400">Registra una nueva cuenta bancaria para gestionar tus transacciones.</p>
                </div>

                <form onSubmit={handleSubmit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Información de la cuenta</CardTitle>
                            <CardDescription>Complete la información básica de la cuenta bancaria</CardDescription>
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

                                <div className="space-y-2">
                                    <Label htmlFor="initial_balance">
                                        Balance inicial <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="initial_balance"
                                        type="number"
                                        step="0.01"
                                        value={data.initial_balance}
                                        onChange={(e) => setData('initial_balance', e.target.value)}
                                        placeholder="0.00"
                                        className={errors.initial_balance ? 'border-red-500' : ''}
                                    />
                                    {errors.initial_balance && <p className="text-sm text-red-500">{errors.initial_balance}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="initial_balance_date">
                                        Fecha del balance inicial <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="initial_balance_date"
                                        type="date"
                                        value={data.initial_balance_date}
                                        onChange={(e) => setData('initial_balance_date', e.target.value)}
                                        className={errors.initial_balance_date ? 'border-red-500' : ''}
                                    />
                                    {errors.initial_balance_date && <p className="text-sm text-red-500">{errors.initial_balance_date}</p>}
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
                        </CardContent>
                    </Card>

                    <div className="flex justify-end space-x-4">
                        <Button type="button" variant="outline" onClick={() => router.get('/bank-accounts')} disabled={processing}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Crear cuenta'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
