import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, Building2, Calendar, CheckCircle, User, UserPlus } from 'lucide-react';
import { useState } from 'react';

interface Invitation {
    id: number;
    email: string;
    role: string;
    workspace: {
        name: string;
        description?: string;
    };
    invited_by: {
        name: string;
    };
    expires_at: string;
}

interface Props {
    invitation: Invitation;
}

export default function AcceptInvitation({ invitation }: Props) {
    const { errors, flash } = usePage().props as any;
    const [processing, setProcessing] = useState(false);

    const handleAccept = () => {
        setProcessing(true);
        router.post(
            `/invitations/${window.location.pathname.split('/').pop()}/accept`,
            {},
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    const handleDecline = () => {
        router.post(`/invitations/${window.location.pathname.split('/').pop()}/decline`);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4">
            <Head title="Invitación a la sucursal" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                        <UserPlus className="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle className="text-2xl">Invitación a la sucursal</CardTitle>
                    <CardDescription>Has sido invitado a unirte a una sucursal</CardDescription>
                </CardHeader>

                <CardContent className="space-y-6">
                    {/* Error Messages */}
                    {errors.invitation && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{errors.invitation}</AlertDescription>
                        </Alert>
                    )}

                    {/* Flash Messages */}
                    {flash.message && (
                        <Alert>
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{flash.message}</AlertDescription>
                        </Alert>
                    )}

                    <div className="space-y-4">
                        <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                            <Building2 className="h-5 w-5 text-gray-600" />
                            <div>
                                <p className="font-medium">{invitation.workspace.name}</p>
                                {invitation.workspace.description && <p className="text-sm text-gray-600">{invitation.workspace.description}</p>}
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                            <User className="h-5 w-5 text-gray-600" />
                            <div>
                                <p className="text-sm text-gray-600">Invitado por</p>
                                <p className="font-medium">{invitation.invited_by.name}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-lg bg-gray-50 p-3">
                            <CheckCircle className="h-5 w-5 text-gray-600" />
                            <div>
                                <p className="text-sm text-gray-600">Tu rol será</p>
                                <Badge variant="secondary" className="mt-1">
                                    {invitation.role}
                                </Badge>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 rounded-lg border border-primary/80 bg-primary/10 p-3">
                            <Calendar className="h-5 w-5 text-primary" />
                            <div>
                                <p className="text-sm text-primary/80">Esta invitación expira el</p>
                                <p className="font-medium text-primary/90">{formatDate(invitation.expires_at)}</p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Button onClick={handleAccept} disabled={processing} className="w-full bg-green-600 hover:bg-green-700">
                            {processing ? 'Procesando...' : 'Aceptar Invitación'}
                        </Button>

                        <Button onClick={handleDecline} variant="outline" className="w-full">
                            Rechazar
                        </Button>
                    </div>

                    <p className="text-center text-xs text-gray-500">
                        Al aceptar esta invitación, tendrás acceso al workspace "{invitation.workspace.name}" con el rol de {invitation.role}.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
