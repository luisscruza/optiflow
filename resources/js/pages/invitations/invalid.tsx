import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';
import { XCircle, Home } from 'lucide-react';

interface Props {
    message: string;
}

export default function InvalidInvitation({ message }: Props) {
    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
            <Head title="Invitación no válida" />
            
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <XCircle className="h-8 w-8 text-red-600" />
                    </div>
                    <CardTitle className="text-2xl text-red-600">Invitación no válida</CardTitle>
                    <CardDescription>
                        No es posible procesar esta invitación
                    </CardDescription>
                </CardHeader>
                
                <CardContent className="space-y-6 text-center">
                    <p className="text-gray-600">{message}</p>
                    
                    <div className="space-y-3">
                        <Link href="/" className="block">
                            <Button className="w-full gap-2">
                                <Home className="h-4 w-4" />
                                Ir al Inicio
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}