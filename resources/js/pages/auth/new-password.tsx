import PasswordChangeController from '@/actions/App/Http/Controllers/PasswordChangeController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function NewPassword() {
    return (
        <AuthLayout title="Cambia tu contraseña" description="Por seguridad, debes establecer una nueva contraseña antes de continuar.">
            <Head title="Nueva contraseña" />

            <Form {...PasswordChangeController.update.form()} resetOnSuccess={['password', 'password_confirmation']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">Nueva contraseña</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Nueva contraseña"
                                autoComplete="new-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                placeholder="Confirmar contraseña"
                                autoComplete="new-password"
                            />

                            <InputError message={errors.password_confirmation} />
                        </div>

                        <div className="flex items-center">
                            <Button className="w-full" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Cambiar contraseña
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
