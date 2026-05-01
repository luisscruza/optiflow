import EasyFactuSettingsController from '@/actions/App/Http/Controllers/Settings/EasyFactuSettingsController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface Settings {
    environment: string;
    api_key_testecf: string;
    api_key_certecf: string;
    api_key_ecf: string;
    base_url: string;
    has_key_testecf: boolean;
    has_key_certecf: boolean;
    has_key_ecf: boolean;
}

interface Props {
    settings: Settings;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Facturación electrónica',
        href: EasyFactuSettingsController.show.url(),
    },
];

const environments = [
    {
        value: 'TesteCF',
        label: 'TesteCF (Pruebas)',
        description: 'Entorno de pruebas. Las facturas no se envian a la DGII.',
        badgeColor: 'bg-primary/10 text-primary/80',
    },
    {
        value: 'CerteCF',
        label: 'CerteCF (Certificacion)',
        description: 'Entorno de certificacion de la DGII. Las facturas se validan pero no tienen efecto fiscal.',
        badgeColor: 'bg-blue-100 text-blue-800',
    },
    {
        value: 'eCF',
        label: 'eCF (Produccion)',
        description: 'Entorno de produccion. Las facturas tienen efecto fiscal real.',
        badgeColor: 'bg-green-100 text-green-800',
    },
];

export default function ElectronicInvoicing({ settings }: Props) {
    const { flash } = usePage().props as any;
    const [testingConnection, setTestingConnection] = useState(false);
    const [selectedEnvironment, setSelectedEnvironment] = useState(settings.environment);

    const handleTestConnection = () => {
        setTestingConnection(true);
        router.post(
            EasyFactuSettingsController.testConnection.url(),
            {},
            {
                preserveScroll: true,
                onFinish: () => setTestingConnection(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturación electrónica" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Facturación electrónica</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Configura la conexión con la DGII para emitir comprobantes fiscales electrónicos (e-CF).
                        </p>
                    </div>

                    <Button type="button" variant="outline" asChild>
                        <a href="/configuration">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </a>
                    </Button>
                </div>

                <Card>
                    <CardContent className="space-y-6 p-6">
                        <HeadingSmall
                            title="Ambiente EasyFactu"
                            description="Selecciona el entorno DGII activo y administra las claves API por ambiente."
                        />

                        <Form
                            {...EasyFactuSettingsController.update.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            className="space-y-6"
                        >
                            {({ processing, recentlySuccessful, errors }) => (
                                <>
                                    {/* Environment Selection */}
                                    <div className="space-y-3">
                                        <Label className="text-sm font-medium">Entorno DGII</Label>
                                        <div className="space-y-2">
                                            {environments.map((env) => (
                                                <label
                                                    key={env.value}
                                                    className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors ${
                                                        selectedEnvironment === env.value
                                                            ? 'border-primary bg-primary/5'
                                                            : 'border-gray-200 hover:border-gray-300'
                                                    }`}
                                                >
                                                    <input
                                                        type="radio"
                                                        name="environment"
                                                        value={env.value}
                                                        defaultChecked={settings.environment === env.value}
                                                        className="mt-1"
                                                        onChange={(e) => setSelectedEnvironment(e.target.value)}
                                                    />
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm font-medium">{env.label}</span>
                                                            <Badge className={env.badgeColor}>{env.value}</Badge>
                                                        </div>
                                                        <p className="mt-1 text-xs text-muted-foreground">{env.description}</p>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError message={(errors as any).environment} />
                                    </div>

                                    {/* API Keys */}
                                    <div className="space-y-4">
                                        <Label className="text-sm font-medium">Claves API</Label>
                                        <p className="text-xs text-muted-foreground">
                                            Ingresa la clave API para cada entorno. Solo es requerida la clave del entorno activo. Las claves
                                            existentes se muestran enmascaradas — pega una nueva para reemplazar.
                                        </p>

                                        <div className="grid gap-3">
                                            <div className="grid gap-2">
                                                <Label htmlFor="api_key_testecf" className="text-xs">
                                                    Clave TesteCF
                                                    {settings.has_key_testecf && (
                                                        <Badge variant="outline" className="ml-2 text-xs">
                                                            Configurada
                                                        </Badge>
                                                    )}
                                                </Label>
                                                <Input
                                                    id="api_key_testecf"
                                                    name="api_key_testecf"
                                                    type="password"
                                                    placeholder={settings.has_key_testecf ? settings.api_key_testecf : 'ef_testecf_...'}
                                                    autoComplete="off"
                                                />
                                                <InputError message={(errors as any).api_key_testecf} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="api_key_certecf" className="text-xs">
                                                    Clave CerteCF
                                                    {settings.has_key_certecf && (
                                                        <Badge variant="outline" className="ml-2 text-xs">
                                                            Configurada
                                                        </Badge>
                                                    )}
                                                </Label>
                                                <Input
                                                    id="api_key_certecf"
                                                    name="api_key_certecf"
                                                    type="password"
                                                    placeholder={settings.has_key_certecf ? settings.api_key_certecf : 'ef_certecf_...'}
                                                    autoComplete="off"
                                                />
                                                <InputError message={(errors as any).api_key_certecf} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="api_key_ecf" className="text-xs">
                                                    Clave eCF (Produccion)
                                                    {settings.has_key_ecf && (
                                                        <Badge variant="outline" className="ml-2 text-xs">
                                                            Configurada
                                                        </Badge>
                                                    )}
                                                </Label>
                                                <Input
                                                    id="api_key_ecf"
                                                    name="api_key_ecf"
                                                    type="password"
                                                    placeholder={settings.has_key_ecf ? settings.api_key_ecf : 'ef_ecf_...'}
                                                    autoComplete="off"
                                                />
                                                <InputError message={(errors as any).api_key_ecf} />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Advanced: Base URL */}
                                    <details className="space-y-2">
                                        <summary className="cursor-pointer text-sm font-medium text-muted-foreground">Configuracion avanzada</summary>
                                        <div className="mt-2 grid gap-2">
                                            <Label htmlFor="base_url" className="text-xs">
                                                URL base de la API
                                            </Label>
                                            <Input
                                                id="base_url"
                                                name="base_url"
                                                type="url"
                                                defaultValue={settings.base_url}
                                                placeholder="https://app.easyfactu.com/api"
                                            />
                                            <InputError message={(errors as any).base_url} />
                                            <p className="text-xs text-muted-foreground">Solo modifica esto si usas una instancia personalizada.</p>
                                        </div>
                                    </details>

                                    {/* Actions */}
                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>Guardar</Button>

                                        <Button type="button" variant="outline" onClick={handleTestConnection} disabled={testingConnection}>
                                            {testingConnection ? 'Probando...' : 'Probar conexion'}
                                        </Button>

                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">Guardado</p>
                                        </Transition>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
