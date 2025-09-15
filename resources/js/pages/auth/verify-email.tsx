// Components
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <AuthLayout
            title="Verificar correo electrónico"
            description="Por favor verifica tu dirección de correo electrónico haciendo clic en el enlace que te acabamos de enviar."
        >
            <Head title="Verificación de correo electrónico" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico que proporcionaste durante el registro.
                </div>
            )}

            <Form {...EmailVerificationNotificationController.store.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Reenviar correo de verificación
                        </Button>

                        <TextLink href={logout()} className="mx-auto block text-sm">
                            Cerrar sesión
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
