import { Form, router } from '@inertiajs/react';
import { ArrowRight, Search } from 'lucide-react';
import { useState, useEffect, useRef } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import countriesData from '@/data/countries.json';
import { type ContactType, type IdentificationType } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onAdvancedForm?: () => void;
    onSuccess?: (contact: any) => void;
    contact_types?: Array<{ value: ContactType; label: string }>;
    identification_types?: Array<{ value: IdentificationType; label: string }>;
}

export default function QuickContactModal({
    open,
    onOpenChange,
    onAdvancedForm,
    onSuccess,
    contact_types = [
        { value: 'customer', label: 'Cliente' },
        { value: 'supplier', label: 'Proveedor' },
    ],
    identification_types = [
        { value: 'cedula', label: 'Cédula' },
        { value: 'rnc', label: 'RNC' },
        { value: 'pasaporte', label: 'Pasaporte' },
    ],
}: Props) {
    const [selectedContactType, setSelectedContactType] = useState<ContactType | ''>('customer');
    const [selectedIdentificationType, setSelectedIdentificationType] = useState<IdentificationType | ''>('');
    const [identificationNumber, setIdentificationNumber] = useState('');
    const [contactName, setContactName] = useState('');
    const [isSearchingRNC, setIsSearchingRNC] = useState(false);
    const [rncSearchError, setRncSearchError] = useState<string | null>(null);
    
    const identificationTypeRef = useRef<HTMLSelectElement>(null);
    const identificationNumberRef = useRef<HTMLInputElement>(null);
    const contactNameRef = useRef<HTMLInputElement>(null);

    const dominicanProvinces = countriesData['Dominican Republic'] || [];

    // Reset form state when modal opens
    useEffect(() => {
        if (open) {
            setSelectedContactType('customer');
            setSelectedIdentificationType('');
            setIdentificationNumber('');
            setContactName('');
            setIsSearchingRNC(false);
            setRncSearchError(null);
            
            // Reset form inputs
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
        // Clear identification number when changing type
        if (value !== 'rnc') {
            setIdentificationNumber('');
            if (identificationNumberRef.current) {
                identificationNumberRef.current.value = '';
            }
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
                    <DialogTitle className="flex items-center justify-between">Nuevo contacto</DialogTitle>
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
                    className="space-y-6"
                >
                    {({ errors, processing, wasSuccessful }) => (
                        <>
                            <input type="hidden" name="status" value="active" />

                            {/* Contact Type Selection */}
                            <div className="grid grid-cols-2 gap-4">
                                {contact_types.map((type) => (
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
                                                    ? 'border-gray-500 bg-gray-50 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300'
                                                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                                            }`}
                                        >
                                            <div className="font-medium">{type.label}</div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                            {errors.contact_type && <p className="text-sm text-red-600 dark:text-red-400">{errors.contact_type}</p>}

                            {/* Identification */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="identification_type">Tipo de identificación</Label>
                                    <select
                                        ref={identificationTypeRef}
                                        name="identification_type"
                                        onChange={handleIdentificationTypeChange}
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Seleccionar</option>
                                        {identification_types.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.identification_type && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{errors.identification_type}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="identification_number">Número</Label>
                                    <div className="flex gap-2">
                                        <Input 
                                            ref={identificationNumberRef}
                                            name="identification_number" 
                                            type="text" 
                                            placeholder="Número de identificación"
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
                                    {errors.identification_number && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{errors.identification_number}</p>
                                    )}
                                    {rncSearchError && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{rncSearchError}</p>
                                    )}
                                </div>
                            </div>

                            {/* Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Nombre o Razón social *</Label>
                                <Input 
                                    ref={contactNameRef}
                                    name="name" 
                                    type="text" 
                                    placeholder="Nombre completo o razón social" 
                                    required 
                                    onChange={(e) => setContactName(e.target.value)}
                                />
                                {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                            </div>

                            {/* Address */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="municipality">Provincia</Label>
                                    <select
                                        name="address.municipality"
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <option value="">Seleccionar provincia</option>
                                        {dominicanProvinces.map((province) => (
                                            <option key={province} value={province.toLowerCase().replace(/\s+/g, '-')}>
                                                {province}
                                            </option>
                                        ))}
                                    </select>
                                    {errors['address.municipality'] && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{errors['address.municipality']}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Dirección</Label>
                                    <Input name="address.description" type="text" placeholder="Dirección completa" />
                                    {errors['address.description'] && (
                                        <p className="text-sm text-red-600 dark:text-red-400">{errors['address.description']}</p>
                                    )}
                                </div>
                            </div>

                            {/* Contact Info */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="email">Correo electrónico</Label>
                                    <Input name="email" type="email" placeholder="ejemplo@email.com" />
                                    {errors.email && <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone_primary">Teléfono</Label>
                                    <Input name="phone_primary" type="tel" placeholder="000-000-0000" />
                                    {errors.phone_primary && <p className="text-sm text-red-600 dark:text-red-400">{errors.phone_primary}</p>}
                                </div>
                            </div>

                            {/* Success Message */}
                            {wasSuccessful && (
                                <div className="rounded-lg bg-green-50 p-4 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                    ¡Contacto creado exitosamente!
                                </div>
                            )}

                            {/* Action Buttons */}
                            <div className="flex items-center justify-between pt-4">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={handleAdvancedForm}
                                    className="flex items-center text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                >
                                    <ArrowRight className="mr-2 h-4 w-4" />
                                    Ir a formulario avanzado
                                </Button>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-gray-600 hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-700"
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
