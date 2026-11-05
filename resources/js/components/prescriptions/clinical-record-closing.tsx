import { useForm } from '@inertiajs/react';
import { FilePlus2, Lock, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

import ClosePrescriptionController from '@/actions/App/Http/Controllers/ClosePrescriptionController';
import { store as storeAddendum } from '@/actions/App/Http/Controllers/PrescriptionAddendumController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type Prescription } from '@/types';

const formatDateTime = (value: string): string =>
    new Date(value).toLocaleString('es-DO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const pinInputProps = {
    type: 'password',
    inputMode: 'numeric' as const,
    autoComplete: 'off',
    maxLength: 6,
};

export function ClosePrescriptionButton({ prescription, optometristHasPin }: { prescription: Prescription; optometristHasPin: boolean }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ pin: '', pin_confirmation: '' });
    const optometristName = prescription.optometrist?.name;

    const submit = (e: React.FormEvent): void => {
        e.preventDefault();
        form.post(ClosePrescriptionController.url(prescription.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
            onError: () => form.reset('pin', 'pin_confirmation'),
        });
    };

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <Lock className="mr-2 h-4 w-4" />
                Cerrar historia clínica
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Cerrar historia clínica</DialogTitle>
                            <DialogDescription>
                                Una vez cerrada no se puede editar ni reabrir. Las correcciones se hacen con una adenda.
                            </DialogDescription>
                        </DialogHeader>

                        {!optometristName ? (
                            <p className="my-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                                Asigna un evaluador a la receta antes de cerrarla.
                            </p>
                        ) : (
                            <div className="my-4 space-y-3">
                                {optometristHasPin ? (
                                    <p className="text-sm text-muted-foreground">
                                        Solo <span className="font-semibold text-foreground">{optometristName}</span> puede cerrarla con su PIN.
                                    </p>
                                ) : (
                                    <p className="rounded-md bg-blue-50 p-3 text-sm text-blue-800">
                                        {optometristName} todavía no tiene PIN. Crea su PIN ahora (4 a 6 dígitos); lo usará para cerrar sus historias
                                        y firmar adendas.
                                    </p>
                                )}
                                <div className="space-y-1">
                                    <Label htmlFor="close-pin">{optometristHasPin ? `PIN de ${optometristName}` : 'Nuevo PIN'}</Label>
                                    <Input
                                        id="close-pin"
                                        {...pinInputProps}
                                        autoFocus
                                        value={form.data.pin}
                                        onChange={(e) => form.setData('pin', e.target.value.replace(/\D/g, ''))}
                                    />
                                    <InputError message={form.errors.pin} />
                                </div>
                                {!optometristHasPin && (
                                    <div className="space-y-1">
                                        <Label htmlFor="close-pin-confirmation">Confirmar PIN</Label>
                                        <Input
                                            id="close-pin-confirmation"
                                            {...pinInputProps}
                                            value={form.data.pin_confirmation}
                                            onChange={(e) => form.setData('pin_confirmation', e.target.value.replace(/\D/g, ''))}
                                        />
                                        <InputError message={form.errors.pin_confirmation} />
                                    </div>
                                )}
                            </div>
                        )}

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={!optometristName || form.processing || form.data.pin.length < 4}>
                                <Lock className="mr-2 h-4 w-4" />
                                {form.processing ? 'Cerrando...' : 'Cerrar historia'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

export function ClinicalRecordAddenda({ prescription, canAdd }: { prescription: Prescription; canAdd: boolean }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ content: '', pin: '' });
    const signer = prescription.closed_by_optometrist?.name ?? prescription.optometrist?.name ?? 'el evaluador';

    if (!prescription.closed_at) {
        return null;
    }

    const submit = (e: React.FormEvent): void => {
        e.preventDefault();
        form.post(storeAddendum.url(prescription.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
            onError: () => form.reset('pin'),
        });
    };

    return (
        <div className="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900 print:hidden">
            <div className="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <ShieldCheck className="h-5 w-5 text-green-600" />
                        Historia clínica cerrada
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Cerrada el {formatDateTime(prescription.closed_at)} con el PIN de {signer}
                        {prescription.closed_by_user && ` (sesión de ${prescription.closed_by_user.name})`}.
                    </p>
                </div>
                {canAdd && (
                    <Button size="sm" variant="outline" onClick={() => setOpen(true)}>
                        <FilePlus2 className="mr-2 h-4 w-4" />
                        Agregar adenda
                    </Button>
                )}
            </div>

            <h3 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Adendas y notas aclaratorias</h3>
            {prescription.addenda && prescription.addenda.length > 0 ? (
                <ul className="space-y-3">
                    {prescription.addenda.map((addendum) => (
                        <li key={addendum.id} className="rounded-md border-l-4 border-blue-400 bg-blue-50/50 p-3 dark:bg-blue-950/30">
                            <p className="text-xs text-muted-foreground">
                                {formatDateTime(addendum.created_at)} · Firmada por {addendum.optometrist?.name ?? 'evaluador'}
                                {addendum.user && ` · registrada por ${addendum.user.name}`}
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-line">{addendum.content}</p>
                        </li>
                    ))}
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">No hay adendas.</p>
            )}

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={submit}>
                        <DialogHeader>
                            <DialogTitle>Agregar adenda</DialogTitle>
                            <DialogDescription>La adenda queda registrada de forma permanente y la firma {signer} con su PIN.</DialogDescription>
                        </DialogHeader>
                        <div className="my-4 space-y-3">
                            <div className="space-y-1">
                                <Label htmlFor="addendum-content">Nota aclaratoria</Label>
                                <Textarea
                                    id="addendum-content"
                                    rows={5}
                                    value={form.data.content}
                                    onChange={(e) => form.setData('content', e.target.value)}
                                />
                                <InputError message={form.errors.content} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="addendum-pin">PIN de {signer}</Label>
                                <Input
                                    id="addendum-pin"
                                    {...pinInputProps}
                                    value={form.data.pin}
                                    onChange={(e) => form.setData('pin', e.target.value.replace(/\D/g, ''))}
                                />
                                <InputError message={form.errors.pin} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={form.processing || !form.data.content.trim() || form.data.pin.length < 4}>
                                {form.processing ? 'Guardando...' : 'Guardar adenda'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
