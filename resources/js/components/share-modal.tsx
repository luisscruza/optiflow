import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type ShareData } from '@/types';
import { Mail, MessageCircle, Share2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface ShareModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    share: ShareData;
    title: string;
}

export default function ShareModal({ open, onOpenChange, share, title }: ShareModalProps) {
    const [channel, setChannel] = useState<'email' | 'whatsapp'>('email');
    const [emailTo, setEmailTo] = useState(share.targets.email ?? '');
    const [emailSubject, setEmailSubject] = useState(share.templates.email?.subject ?? '');
    const [emailBody, setEmailBody] = useState(share.templates.email?.body ?? '');
    const [whatsappPhone, setWhatsappPhone] = useState(share.targets.phone ?? '');
    const [whatsappBody, setWhatsappBody] = useState(share.templates.whatsapp?.body ?? '');

    useEffect(() => {
        if (!open) {
            return;
        }

        setChannel(share.templates.email ? 'email' : 'whatsapp');
        setEmailTo(share.targets.email ?? '');
        setEmailSubject(share.templates.email?.subject ?? '');
        setEmailBody(share.templates.email?.body ?? '');
        setWhatsappPhone(share.targets.phone ?? '');
        setWhatsappBody(share.templates.whatsapp?.body ?? '');
    }, [open, share]);

    const emailHref = useMemo(() => {
        if (emailTo.trim() === '') {
            return null;
        }

        const params = new URLSearchParams();

        if (emailSubject.trim() !== '') {
            params.set('subject', emailSubject);
        }

        if (emailBody.trim() !== '') {
            params.set('body', emailBody);
        }

        const query = params.toString();

        return `mailto:${emailTo}${query ? `?${query}` : ''}`;
    }, [emailBody, emailSubject, emailTo]);

    const whatsappHref = useMemo(() => {
        const phone = normalizePhone(whatsappPhone);

        if (phone === '') {
            return null;
        }

        return `https://wa.me/${phone}?text=${encodeURIComponent(whatsappBody)}`;
    }, [whatsappBody, whatsappPhone]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-xl">
                        <Share2 className="h-5 w-5" />
                        Compartir {title}
                    </DialogTitle>
                    <DialogDescription>El enlace compartible expira en 30 días.</DialogDescription>
                </DialogHeader>

                <div className="space-y-5">
                    <div className="rounded-lg border bg-gray-50 p-4">
                        <Label htmlFor="shareable-link">Enlace compartible</Label>
                        <Input id="shareable-link" value={share.shareableLink} readOnly className="mt-2" />
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2">
                        <button
                            type="button"
                            onClick={() => setChannel('email')}
                            className={`rounded-lg border p-4 text-left transition ${channel === 'email' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'}`}
                        >
                            <div className="flex items-center gap-2 font-medium text-gray-900">
                                <Mail className="h-4 w-4" />
                                Enviar por correo
                            </div>
                            <p className="mt-2 text-sm text-gray-600">{share.targets.email || 'El contacto no tiene correo registrado.'}</p>
                        </button>

                        <button
                            type="button"
                            onClick={() => setChannel('whatsapp')}
                            className={`rounded-lg border p-4 text-left transition ${channel === 'whatsapp' ? 'border-green-500 bg-green-50' : 'border-gray-200'}`}
                        >
                            <div className="flex items-center gap-2 font-medium text-gray-900">
                                <MessageCircle className="h-4 w-4" />
                                Enviar por WhatsApp
                            </div>
                            <p className="mt-2 text-sm text-gray-600">{share.targets.phone || 'El contacto no tiene telefono registrado.'}</p>
                        </button>
                    </div>

                    {channel === 'email' ? (
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="email-to">Correo de destino</Label>
                                <Input id="email-to" value={emailTo} onChange={(event) => setEmailTo(event.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email-subject">Asunto</Label>
                                <Input id="email-subject" value={emailSubject} onChange={(event) => setEmailSubject(event.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email-body">Cuerpo del correo</Label>
                                <Textarea id="email-body" value={emailBody} onChange={(event) => setEmailBody(event.target.value)} rows={10} />
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="whatsapp-phone">Numero de destino</Label>
                                <Input id="whatsapp-phone" value={whatsappPhone} onChange={(event) => setWhatsappPhone(event.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="whatsapp-body">Mensaje</Label>
                                <Textarea
                                    id="whatsapp-body"
                                    value={whatsappBody}
                                    onChange={(event) => setWhatsappBody(event.target.value)}
                                    rows={8}
                                />
                            </div>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                        Cerrar
                    </Button>

                    {channel === 'email' ? (
                        <Button asChild disabled={!emailHref}>
                            <a href={emailHref ?? undefined}>
                                <Mail className="mr-2 h-4 w-4" />
                                Abrir correo
                            </a>
                        </Button>
                    ) : (
                        <Button asChild disabled={!whatsappHref} className="bg-green-600 hover:bg-green-700">
                            <a href={whatsappHref ?? undefined} target="_blank" rel="noreferrer">
                                <MessageCircle className="mr-2 h-4 w-4" />
                                Abrir WhatsApp
                            </a>
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function normalizePhone(value: string): string {
    return value.replace(/\D/g, '');
}
