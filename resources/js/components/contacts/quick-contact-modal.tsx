import { Form } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { Value } from 'react-phone-number-input';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PhoneInput from '@/components/ui/phone-input';
import { type ContactType, type IdentificationType } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAdvancedForm?: () => void;
    onSuccess?: (contact: any) => void;
    contact_types?: Array<{ value: ContactType; label: string }>;
    identification_types?: Array<{ value: IdentificationType; label: string }>;
    types?: ContactType[];
}

interface DuplicateWarnings {
    email?: { id: number; name: string };
    phone?: { id: number; name: string };
}

export default function QuickContactModal({
    open,
    onOpenChange,
    onAdvancedForm,
    onSuccess,
    contact_types = [
        { value: 'customer', label: 'Cliente' },
        { value: 'supplier', label: 'Proveedor' },
        { value: 'optometrist', label: 'Evaluador' },
    ],
    identification_types = [
        { value: 'cedula', label: 'Cédula' },
        { value: 'rnc', label: 'RNC' },
        { value: 'pasaporte', label: 'Pasaporte' },
    ],
    types = ['customer', 'supplier'],
}: Props) {
    const [selectedContactType, setSelectedContactType] = useState<ContactType | ''>('customer');
    const [selectedIdentificationType, setSelectedIdentificationType] = useState<IdentificationType | ''>('');
    const [identificationNumber, setIdentificationNumber] = useState('');
    const [contactName, setContactName] = useState('');
    const [phonePrimary, setPhonePrimary] = useState<Value | undefined>();
    const [phoneSecondary, setPhoneSecondary] = useState<Value | undefined>();
    const [isSearchingRNC, setIsSearchingRNC] = useState(false);
    const [rncSearchError, setRncSearchError] = useState<string | null>(null);
    const [duplicateWarnings, setDuplicateWarnings] = useState<DuplicateWarnings>({});
    const [duplicatesAcknowledged, setDuplicatesAcknowledged] = useState(false);

    const identificationTypeRef = useRef<HTMLSelectElement>(null);
    const identificationNumberRef = useRef<HTMLInputElement>(null);
    const contactNameRef = useRef<HTMLInputElement>(null);

    // Reset form state when modal opens
    useEffect(() => {
        if (open) {
            setSelectedContactType('customer');
            setSelectedIdentificationType('');
            setIdentificationNumber('');
            setContactName('');
            setPhonePrimary(undefined);
            setPhoneSecondary(undefined);
            setIsSearchingRNC(false);
            setRncSearchError(null);
            setDuplicateWarnings({});
            setDuplicatesAcknowledged(false);

            if (identificationTypeRef.current) {
                identificationTypeRef.current.value = '';
            }
            if (identificationNumberRef.current) {
                identificationNumberRef.current.value = '';
            }
            if (contactNameRef.current) {
                contactNameRef.current.value = '';
            }
        }
    }, [open]);

    const handleAdvancedForm = () => {
        onOpenChange(false);
        if (onAdvancedForm) {
            onAdvancedForm();
        }
    };

    const handleIdentificationTypeChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const value = e.target.value as IdentificationType;
        setSelectedIdentificationType(value);
        setRncSearchError(null);
        if (value !== 'rnc') {
            setIdentificationNumber('');
            if (identificationNumberRef.current) {
                identificationNumberRef.current.value = '';
            }
        }
    };

    const checkDuplicates = async (field: 'email' | 'phone', value: string) => {
        if (!value) {
            setDuplicateWarnings((prev) => ({ ...prev, [field]: undefined }));
            setDuplicatesAcknowledged(false);
            return;
        }

        const params = new URLSearchParams({ [field === 'email' ? 'email' : 'phone']: value });

        try {
            const response = await fetch(`/api/contacts/check-duplicates?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) {
                const data = await response.json();
                setDuplicateWarnings((prev) => ({ ...prev, [field]: data[field] }));
                setDuplicatesAcknowledged(false);
            }
        } catch {
            // silently ignore network errors
        }
    };

    const searchRNC = async () => {
        if (!identificationNumber || selectedIdentificationType !== 'rnc') return;

        setIsSearchingRNC(true);
        setRncSearchError(null);

        try {
            const response = await fetch('/api/rnc', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ rnc: identificationNumber }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.name) {
                    setContactName(data.name);
                    if (contactNameRef.current) {
                        contactNameRef.current.value = data.name;
                    }
                }
            } else {
                setRncSearchError('RNC no encontrado o error en la búsqueda');
            }
        } catch (error) {
            setRncSearchError('Error al buscar el RNC. Intente nuevamente.');
        } finally {
            setIsSearchingRNC(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Nuevo contacto</DialogTitle>
                </DialogHeader>

                <Form
                    action="/contacts"
                    method="post"
                    resetOnSuccess
                    onSuccess={(page: any) => {
                        onOpenChange(false);
                        if (onSuccess) {
                            const contact = page.props.newlyCreatedContact;
                            onSuccess(contact);
                        }
                    }}
                    className="space-y-5"
                >
                    {({ errors, processing, wasSuccessful }) => (
                        <>
                            <input type="hidden" name="status" value="active" />
                            <input type="hidden" name="phone_primary" value={phonePrimary || ''} />
                            <input type="hidden" name="phone_secondary" value={phoneSecondary || ''} />

                            {/* Contact Type */}
                            <div className="grid grid-cols-2 gap-3">
                                {contact_types
                                    .filter((type) => types.includes(type.value))
                                    .map((type) => (
                                        <label key={type.value} className="cursor-pointer">
                                            <input
                                                type="radio"
                                                name="contact_type"
                                                value={type.value}
                                                className="sr-only"
                                                checked={selectedContactType === type.value}
                                                onChange={() => setSelectedContactType(type.value)}
                                            />
                                            <div
                                                className={`rounded-lg border py-2.5 text-center transition-colors ${
                                                    selectedContactType === type.value
                                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                        : 'border-border hover:border-primary/40'
                                                }`}
                                            >
                                                <div className="text-sm font-medium">{type.label}</div>
                                            </div>
                                        </label>
                                    ))}
                            </div>
                            <InputError message={errors.contact_type} />

                            {/* Identification */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="quick_identification_type">Tipo de identificacion</Label>
                                    <select
                                        ref={identificationTypeRef}
                                        name="identification_type"
                                        onChange={handleIdentificationTypeChange}
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Seleccionar</option>
                                        {identification_types.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.identification_type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="quick_identification_number">Numero</Label>
                                    <div className="flex gap-2">
                                        <Input
                                            ref={identificationNumberRef}
                                            name="identification_number"
                                            type="text"
                                            placeholder="Numero de identificacion"
                                            onChange={(e) => {
                                                setIdentificationNumber(e.target.value);
                                                setRncSearchError(null);
                                            }}
                                        />
                                        {selectedIdentificationType === 'rnc' && (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                onClick={searchRNC}
                                                disabled={!identificationNumber || isSearchingRNC}
                                                className="shrink-0"
                                            >
                                                <Search className={`h-4 w-4 ${isSearchingRNC ? 'animate-spin' : ''}`} />
                                            </Button>
                                        )}
                                    </div>
                                    <InputError message={errors.identification_number} />
                                    {rncSearchError && <p className="text-sm text-destructive">{rncSearchError}</p>}
                                </div>
                            </div>

                            {/* Name */}
                            <div className="grid gap-2">
                                <Label htmlFor="quick_name">
                                    Nombre o Razon social <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    ref={contactNameRef}
                                    name="name"
                                    type="text"
                                    placeholder="Nombre completo o razon social"
                                    required
                                    onChange={(e) => setContactName(e.target.value)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Gender & Birth Date */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="quick_gender">Sexo</Label>
                                    <select
                                        name="gender"
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Seleccionar sexo</option>
                                        <option value="male">Masculino</option>
                                        <option value="female">Femenino</option>
                                        <option value="-">Prefiero no decirlo</option>
                                    </select>
                                    <InputError message={errors['gender']} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="quick_birth_date">Fecha de nacimiento</Label>
                                    <Input name="birth_date" type="date" />
                                    <InputError message={errors['birth_date']} />
                                </div>
                            </div>

                            {/* Contact Info */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="quick_email">Correo electronico</Label>
                                    <Input
                                        name="email"
                                        type="email"
                                        placeholder="ejemplo@email.com"
                                        onBlur={(e) => checkDuplicates('email', e.target.value)}
                                    />
                                    <InputError message={errors.email} />
                                    {duplicateWarnings.email && (
                                        <p className="text-sm text-amber-600 dark:text-amber-400">
                                            Ya existe un contacto con este correo: <strong>{duplicateWarnings.email.name}</strong>.
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="quick_phone_primary">
                                        Telefono <span className="text-destructive">*</span>
                                    </Label>
                                    <PhoneInput
                                        value={phonePrimary}
                                        onChange={(value) => setPhonePrimary(value)}
                                        onBlur={() => checkDuplicates('phone', phonePrimary || '')}
                                        placeholder="000-000-0000"
                                    />
                                    <InputError message={errors.phone_primary} />
                                    {duplicateWarnings.phone && (
                                        <p className="text-sm text-amber-600 dark:text-amber-400">
                                            Ya existe un contacto con este telefono: <strong>{duplicateWarnings.phone.name}</strong>.
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Phone Secondary */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="quick_phone_secondary">Telefono secundario</Label>
                                    <PhoneInput value={phoneSecondary} onChange={(value) => setPhoneSecondary(value)} placeholder="000-000-0000" />
                                    <InputError message={errors.phone_secondary} />
                                </div>
                            </div>

                            {/* Success Message */}
                            {wasSuccessful && (
                                <div className="rounded-lg bg-green-50 p-4 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                    Contacto creado exitosamente!
                                </div>
                            )}

                            {/* Duplicate Acknowledgment */}
                            {(duplicateWarnings.email || duplicateWarnings.phone) && (
                                <label className="flex cursor-pointer items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
                                    <input
                                        type="checkbox"
                                        checked={duplicatesAcknowledged}
                                        onChange={(e) => setDuplicatesAcknowledged(e.target.checked)}
                                        className="mt-0.5 h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500"
                                    />
                                    <span className="text-sm text-amber-800 dark:text-amber-300">
                                        Entiendo que ya existe un contacto con datos similares y deseo continuar.
                                    </span>
                                </label>
                            )}

                            {/* Action Buttons */}
                            <div className="flex items-center justify-between pt-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={handleAdvancedForm}
                                    className="flex items-center text-muted-foreground hover:text-foreground"
                                >
                                    <ArrowRight className="mr-2 h-4 w-4" />
                                    Ir a formulario avanzado
                                </Button>

                                <Button
                                    type="submit"
                                    disabled={
                                        processing || (((duplicateWarnings.email || duplicateWarnings.phone) && !duplicatesAcknowledged) as boolean)
                                    }
                                >
                                    {processing ? 'Creando...' : 'Crear contacto'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
