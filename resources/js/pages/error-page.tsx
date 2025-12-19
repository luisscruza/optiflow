import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Home, RefreshCw, ShieldX } from 'lucide-react';

interface ErrorPageProps {
    status: number;
    message?: string;
}

const errorMessages: Record<number, { title: string; description: string; icon: typeof AlertTriangle }> = {
    400: {
        title: 'Solicitud inválida',
        description: 'La solicitud no pudo ser procesada debido a un error en los datos enviados.',
        icon: AlertTriangle,
    },
    401: {
        title: 'No autorizado',
        description: 'Debes iniciar sesión para acceder a este recurso.',
        icon: ShieldX,
    },
    403: {
        title: 'Acceso denegado',
        description: 'No tienes permisos para acceder a este recurso.',
        icon: ShieldX,
    },
    404: {
        title: 'Página no encontrada',
        description: 'Lo sentimos, la página que buscas no existe o ha sido movida.',
        icon: AlertTriangle,
    },
    419: {
        title: 'Sesión expirada',
        description: 'Tu sesión ha expirado. Por favor, actualiza la página e intenta de nuevo.',
        icon: RefreshCw,
    },
    429: {
        title: 'Demasiadas solicitudes',
        description: 'Has realizado demasiadas solicitudes. Por favor, espera un momento antes de intentar de nuevo.',
        icon: AlertTriangle,
    },
    500: {
        title: 'Error del servidor',
        description: 'Ha ocurrido un error interno. Nuestro equipo ha sido notificado.',
        icon: AlertTriangle,
    },
    503: {
        title: 'Servicio no disponible',
        description: 'El servicio está temporalmente fuera de línea. Por favor, intenta más tarde.',
        icon: AlertTriangle,
    },
};

export default function ErrorPage({ status, message }: ErrorPageProps) {
    const error = errorMessages[status] ?? {
        title: 'Error inesperado',
        description: message ?? 'Ha ocurrido un error inesperado. Por favor, intenta de nuevo.',
        icon: AlertTriangle,
    };

    const Icon = error.icon;

    return (
        <>
            <Head title={`${status} - ${error.title}`} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background px-4">
                <div className="mx-auto flex max-w-md flex-col items-center text-center">
                    {/* Icon */}
                    <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-destructive/10">
                        <Icon className="h-10 w-10 text-destructive" />
                    </div>

                    {/* Status Code */}
                    <h1 className="mb-2 text-7xl font-bold tracking-tight text-foreground">{status}</h1>

                    {/* Title */}
                    <h2 className="mb-3 text-2xl font-semibold text-foreground">{error.title}</h2>

                    {/* Description */}
                    <p className="mb-8 text-muted-foreground">{message ?? error.description}</p>

                    {/* Actions */}
                    <div className="flex flex-col gap-3 sm:flex-row">
                        <Button variant="outline" onClick={() => window.history.back()} className="gap-2">
                            <ArrowLeft className="h-4 w-4" />
                            Volver atrás
                        </Button>
                        <Button asChild className="gap-2">
                            <Link href="/">
                                <Home className="h-4 w-4" />
                                Ir al inicio
                            </Link>
                        </Button>
                    </div>

                    {/* Refresh hint for 419 */}
                    {status === 419 && (
                        <Button variant="ghost" onClick={() => window.location.reload()} className="mt-4 gap-2 text-muted-foreground">
                            <RefreshCw className="h-4 w-4" />
                            Actualizar página
                        </Button>
                    )}
                </div>

                {/* Footer */}
                <p className="mt-16 text-sm text-muted-foreground">
                    Si el problema persiste, contacta a{' '}
                    <a href="mailto:soporte@opticanet.com" className="text-primary underline-offset-4 hover:underline">
                        soporte
                    </a>
                </p>
            </div>
        </>
    );
}
