import { router, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';

import { destroy as resetPin, update as updatePin } from '@/actions/App/Http/Controllers/ContactClinicalPinController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    contactId: number;
    contactName: string;
    isSet: boolean;
    canReset: boolean;
}

const digitsOnly = (value: string): string => value.replace(/\D/g, '').slice(0, 6);

export function ClinicalPinCard({ contactId, contactName, isSet, canReset }: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm({ current_pin: '', pin: '', pin_confirmation: '' });

    const submit = (e: React.FormEvent): void => {
        e.preventDefault();
        form.put(updatePin.url(contactId), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
            onError: () => form.reset(),
        });
    };

    const handleReset = (): void => {
        if (confirm(`¿Restablecer el PIN clínico de ${contactName}? Deberá crear uno nuevo para cerrar historias.`)) {
            router.delete(resetPin.url(contactId), { preserveScroll: true });
        }
    };

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-base">
                    <KeyRound className="h-5 w-5" />
                    PIN clínico
                    {isSet ? <Badge className="bg-green-100 text-green-800">Configurado</Badge> : <Badge variant="outline">Sin configurar</Badge>}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                <p className="text-sm text-muted-foreground">Con este PIN el evaluador cierra sus historias clínicas y firma adendas.</p>
                <div className="flex flex-wrap gap-2">
                    <Button size="sm" variant="outline" onClick={() => setOpen(true)}>
                        {isSet ? 'Cambiar PIN' : 'Crear PIN'}
                    </Button>
                    {isSet && canReset && (
                        <Button size="sm" variant="outline" className="text-red-600 hover:text-red-700" onClick={handleReset}>
                            Restablecer
                        </Button>
                    )}
                </div>
            </CardContent>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-sm">
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>{isSet ? 'Cambiar PIN clínico' : 'Crear PIN clínico'}</DialogTitle>
                            <DialogDescription>PIN de {contactName}, de 4 a 6 dígitos.</DialogDescription>
                        </DialogHeader>
                        <div className="my-4 space-y-3">
                            {isSet && (
                                <div className="space-y-1">
                                    <Label htmlFor="current_pin">PIN actual</Label>
                                    <Input
                                        id="current_pin"
                                        type="password"
                                        inputMode="numeric"
                                        autoComplete="off"
                                        value={form.data.current_pin}
                                        onChange={(e) => form.setData('current_pin', digitsOnly(e.target.value))}
                                    />
                                    <InputError message={form.errors.current_pin} />
                                </div>
                            )}
                            <div className="space-y-1">
                                <Label htmlFor="new_pin">Nuevo PIN</Label>
                                <Input
                                    id="new_pin"
                                    type="password"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    value={form.data.pin}
                                    onChange={(e) => form.setData('pin', digitsOnly(e.target.value))}
                                />
                                <InputError message={form.errors.pin} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="new_pin_confirmation">Confirmar PIN</Label>
                                <Input
                                    id="new_pin_confirmation"
                                    type="password"
                                    inputMode="numeric"
                                    autoComplete="off"
                                    value={form.data.pin_confirmation}
                                    onChange={(e) => form.setData('pin_confirmation', digitsOnly(e.target.value))}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={form.processing || form.data.pin.length < 4}>
                                Guardar PIN
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </Card>
    );
}
