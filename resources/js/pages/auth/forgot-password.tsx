// Components
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <AuthLayout title="Olvidé mi contraseña" description="Ingresa tu correo electrónico para recibir un enlace de restablecimiento de contraseña">
            <Head title="Olvidé mi contraseña" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <div className="space-y-6">
                <Form {...PasswordResetLinkController.store.form()}>
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Dirección de correo electrónico</Label>
                                <Input id="email" type="email" name="email" autoComplete="off" autoFocus placeholder="correo@ejemplo.com" />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button className="w-full" disabled={processing}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    Enviar enlace de restablecimiento
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>O, regresar a</span>
                    <TextLink href={login()}>iniciar sesión</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
