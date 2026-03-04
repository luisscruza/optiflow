import { Head, router, useForm } from '@inertiajs/react';
import { Building2, DollarSign, Plus, Save, Trash2, Users } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { Value } from 'react-phone-number-input';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PhoneInput from '@/components/ui/phone-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { ServerSearchableSelect, type ServerSearchableSelectOption } from '@/components/ui/server-searchable-select';
import { Textarea } from '@/components/ui/textarea';
import countriesData from '@/data/countries.json';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ContactType, type IdentificationType } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Contactos',
        href: '/contacts',
    },
    {
        title: 'Crear contacto',
        href: '/contacts/create',
    },
];

export type RelationshipSearchResult = {
    id: number;
    name: string;
    phone_primary: string | null;
    identification_number: string | null;
    email: string | null;
};

interface Props {
    contact_types: Array<{ value: ContactType; label: string }>;
    identification_types: Array<{ value: IdentificationType; label: string }>;
    relationshipSearchResults?: RelationshipSearchResult[];
}

interface ContactFormData {
    contact_type: ContactType | undefined;
    name: string;
    status: 'active' | 'inactive';
    gender: string;
    birth_date: string;
    email: string;
    phone_primary: string;
    phone_secondary: string;
    notes: string;
    identification_type: IdentificationType | undefined;
    identification_number: string;
    credit_limit: string;
    address: {
        municipality: string;
        province: string;
        country: string;
        description: string;
    };
    relationships: Array<{ related_contact_id: string; description: string }>;
}

interface DuplicateWarnings {
    email?: { id: number; name: string };
    phone?: { id: number; name: string };
}

export default function CreateContact({ contact_types, identification_types, relationshipSearchResults = [] }: Props) {
    const [duplicateWarnings, setDuplicateWarnings] = useState<DuplicateWarnings>({});
    const [duplicatesAcknowledged, setDuplicatesAcknowledged] = useState(false);
    const [relationshipSearchQuery, setRelationshipSearchQuery] = useState('');
    const [isSearchingRelationships, setIsSearchingRelationships] = useState(false);
    const relationshipSearchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [relationshipLabels, setRelationshipLabels] = useState<Record<string, string>>({});

    const countries = Object.keys(countriesData);

    const getProvincesForCountry = (country: string) => {
        return countriesData[country as keyof typeof countriesData] || [];
    };

    const { data, setData, post, processing, errors } = useForm<ContactFormData>({
        contact_type: undefined,
        name: '',
        status: 'active',
        gender: '',
        birth_date: '',
        email: '',
        phone_primary: '',
        phone_secondary: '',
        notes: '',
        identification_type: undefined,
        identification_number: '',
        credit_limit: '',
        address: {
            municipality: '',
            province: '',
            country: 'Dominican Republic',
            description: '',
        },
        relationships: [],
    });

    const selectedCountryProvinces = getProvincesForCountry(data.address.country);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.contact_type) {
            return;
        }

        if ((duplicateWarnings.email || duplicateWarnings.phone) && !duplicatesAcknowledged) {
            return;
        }

        post('/contacts');
    };

    const handleCountryChange = (country: string) => {
        setData('address', { ...data.address, country, municipality: '' });
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

    const addRelationship = () => {
        setData('relationships', [...data.relationships, { related_contact_id: '', description: '' }]);
    };

    const removeRelationship = (index: number) => {
        setData(
            'relationships',
            data.relationships.filter((_, i) => i !== index),
        );
    };

    const updateRelationship = (index: number, key: 'related_contact_id' | 'description', value: string) => {
        if (key === 'related_contact_id') {
            const searchResult = relationshipSearchResults.find((c) => c.id.toString() === value);
            if (searchResult) {
                setRelationshipLabels((prev) => ({ ...prev, [value]: searchResult.name }));
            }
        }

        setData(
            'relationships',
            data.relationships.map((relationship, i) => (i === index ? { ...relationship, [key]: value } : relationship)),
        );
    };

    const handleRelationshipSearch = (query: string) => {
        if (relationshipSearchTimeoutRef.current) {
            clearTimeout(relationshipSearchTimeoutRef.current);
        }

        const normalizedQuery = query.trim();
        setRelationshipSearchQuery(normalizedQuery);

        if (normalizedQuery.length < 2) {
            setIsSearchingRelationships(false);
            return;
        }

        relationshipSearchTimeoutRef.current = setTimeout(() => {
            setIsSearchingRelationships(true);
            router.reload({
                only: ['relationshipSearchResults'],
                data: { relationship_search: normalizedQuery },
                onFinish: () => setIsSearchingRelationships(false),
            });
        }, 300);
    };

    useEffect(() => {
        return () => {
            if (relationshipSearchTimeoutRef.current) {
                clearTimeout(relationshipSearchTimeoutRef.current);
            }
        };
    }, []);

    const relationshipOptions: ServerSearchableSelectOption[] = (relationshipSearchQuery.length >= 2 ? relationshipSearchResults : [])
        .filter((contact) => !data.relationships.some((r) => r.related_contact_id === contact.id.toString()))
        .map((contact) => ({
            value: contact.id.toString(),
            label: contact.phone_primary ? `${contact.name} (${contact.phone_primary})` : contact.name,
        }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear contacto" />

            <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-xl font-semibold tracking-tight">Crear contacto</h1>
                    <p className="text-sm text-muted-foreground">Agrega un nuevo cliente o proveedor al sistema.</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Contact Type */}
                    <section className="space-y-4">
                        <HeadingSmall title="Tipo de contacto" description="Selecciona si es cliente o proveedor." />
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            {contact_types.map((type) => (
                                <button
                                    key={type.value}
                                    type="button"
                                    onClick={() => setData('contact_type', type.value)}
                                    className={`flex items-center gap-3 rounded-lg border p-4 text-left transition-colors ${
                                        data.contact_type === type.value
                                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                            : 'border-border hover:border-primary/40'
                                    }`}
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-muted">
                                        {type.value === 'customer' ? <Users className="h-4 w-4" /> : <Building2 className="h-4 w-4" />}
                                    </div>
                                    <div>
                                        <div className="text-sm font-medium">{type.label}</div>
                                        <div className="text-xs text-muted-foreground">
                                            {type.value === 'customer'
                                                ? 'Clientes que compran productos o servicios'
                                                : 'Proveedores de productos o servicios'}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                        <InputError message={errors.contact_type} />
                    </section>

                    <Separator />

                    {/* Basic Information */}
                    <section className="space-y-4">
                        <HeadingSmall title="Informacion basica" />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    Nombre <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Nombre completo o razon social"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">
                                    Estado <span className="text-destructive">*</span>
                                </Label>
                                <Select value={data.status} onValueChange={(value: 'active' | 'inactive') => setData('status', value)}>
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Selecciona el estado" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Activo</SelectItem>
                                        <SelectItem value="inactive">Inactivo</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="gender">
                                    Sexo <span className="text-destructive">*</span>
                                </Label>
                                <Select value={data.gender} onValueChange={(value) => setData('gender', value)}>
                                    <SelectTrigger id="gender">
                                        <SelectValue placeholder="Seleccionar sexo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="male">Masculino</SelectItem>
                                        <SelectItem value="female">Femenino</SelectItem>
                                        <SelectItem value="-">Prefiero no decirlo</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.gender} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="birth_date">Fecha de nacimiento</Label>
                                <Input id="birth_date" type="date" value={data.birth_date} onChange={(e) => setData('birth_date', e.target.value)} />
                                <InputError message={errors.birth_date} />
                            </div>
                        </div>
                    </section>

                    <Separator />

                    {/* Contact Information */}
                    <section className="space-y-4">
                        <HeadingSmall title="Contacto" />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    onBlur={(e) => checkDuplicates('email', e.target.value)}
                                    placeholder="correo@ejemplo.com"
                                />
                                <InputError message={errors.email} />
                                {duplicateWarnings.email && (
                                    <p className="text-sm text-amber-600 dark:text-amber-400">
                                        Ya existe un contacto con este correo: <strong>{duplicateWarnings.email.name}</strong>.
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone_primary">Telefono principal</Label>
                                <PhoneInput
                                    id="phone_primary"
                                    value={(data.phone_primary as Value) || undefined}
                                    onChange={(value?: Value) => setData('phone_primary', value || '')}
                                    onBlur={() => checkDuplicates('phone', data.phone_primary)}
                                    placeholder="000-000-0000"
                                />
                                <InputError message={errors.phone_primary} />
                                {duplicateWarnings.phone && (
                                    <p className="text-sm text-amber-600 dark:text-amber-400">
                                        Ya existe un contacto con este telefono: <strong>{duplicateWarnings.phone.name}</strong>.
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone_secondary">Telefono secundario</Label>
                                <PhoneInput
                                    id="phone_secondary"
                                    value={(data.phone_secondary as Value) || undefined}
                                    onChange={(value?: Value) => setData('phone_secondary', value || '')}
                                    placeholder="000-000-0000"
                                />
                                <InputError message={errors.phone_secondary} />
                            </div>
                        </div>
                    </section>

                    <Separator />

                    {/* Identification */}
                    <section className="space-y-4">
                        <HeadingSmall title="Identificacion" />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="identification_type">Tipo de identificacion</Label>
                                <Select
                                    value={data.identification_type || ''}
                                    onValueChange={(value: IdentificationType) => setData('identification_type', value)}
                                >
                                    <SelectTrigger id="identification_type">
                                        <SelectValue placeholder="Selecciona el tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {identification_types.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.identification_type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="identification_number">Numero de identificacion</Label>
                                <Input
                                    id="identification_number"
                                    value={data.identification_number}
                                    onChange={(e) => setData('identification_number', e.target.value)}
                                    placeholder="123456789"
                                />
                                <InputError message={errors.identification_number} />
                            </div>
                        </div>
                    </section>

                    <Separator />

                    {/* Address */}
                    <section className="space-y-4">
                        <HeadingSmall title="Direccion" />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="country">Pais</Label>
                                <Select value={data.address.country} onValueChange={handleCountryChange}>
                                    <SelectTrigger id="country">
                                        <SelectValue placeholder="Seleccionar pais" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countries.map((country) => (
                                            <SelectItem key={country} value={country}>
                                                {country}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="municipality">Provincia</Label>
                                <Select
                                    value={data.address.municipality}
                                    onValueChange={(value) => setData('address', { ...data.address, municipality: value })}
                                >
                                    <SelectTrigger id="municipality">
                                        <SelectValue placeholder="Seleccionar provincia" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {selectedCountryProvinces.map((province) => (
                                            <SelectItem key={province} value={province.toLowerCase().replace(/\s+/g, '-')}>
                                                {province}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="province">Departamento / Estado</Label>
                                <Input
                                    id="province"
                                    value={data.address.province}
                                    onChange={(e) => setData('address', { ...data.address, province: e.target.value })}
                                    placeholder="Santo Domingo"
                                />
                            </div>

                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="address_description">Direccion</Label>
                                <Textarea
                                    id="address_description"
                                    value={data.address.description}
                                    onChange={(e) => setData('address', { ...data.address, description: e.target.value })}
                                    placeholder="Calle, numero, sector, referencia..."
                                    rows={2}
                                />
                            </div>
                        </div>
                    </section>

                    <Separator />

                    {/* Financial */}
                    <section className="space-y-4">
                        <HeadingSmall title="Informacion financiera" />
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="credit_limit">Limite de credito</Label>
                                <div className="relative">
                                    <DollarSign className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="credit_limit"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.credit_limit}
                                        onChange={(e) => setData('credit_limit', e.target.value)}
                                        placeholder="0.00"
                                        className="pl-9"
                                    />
                                </div>
                                <InputError message={errors.credit_limit} />
                                <p className="text-xs text-muted-foreground">Dejar en 0 para sin limite.</p>
                            </div>
                        </div>
                    </section>

                    <Separator />

                    {/* Relationships */}
                    <section className="space-y-4">
                        <HeadingSmall title="Relaciones" description="Vincula este contacto con familiares o relacionados (opcional)." />

                        {data.relationships.map((relationship, index) => (
                            <div key={index} className="grid grid-cols-1 gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_1fr_auto]">
                                <div className="grid gap-2">
                                    <Label>Contacto relacionado</Label>
                                    <ServerSearchableSelect
                                        options={relationshipOptions}
                                        value={relationship.related_contact_id}
                                        selectedLabel={relationshipLabels[relationship.related_contact_id]}
                                        onValueChange={(value) => updateRelationship(index, 'related_contact_id', value)}
                                        onSearchChange={handleRelationshipSearch}
                                        isLoading={isSearchingRelationships}
                                        placeholder="Seleccionar contacto"
                                        searchPlaceholder="Buscar contacto..."
                                        emptyText="No se encontraron contactos"
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Descripcion (opcional)</Label>
                                    <Input
                                        value={relationship.description}
                                        onChange={(e) => updateRelationship(index, 'description', e.target.value)}
                                        placeholder="Ej: Padre, Hijo, Esposa"
                                    />
                                </div>
                                <div className="flex items-end">
                                    <Button type="button" variant="outline" size="icon" onClick={() => removeRelationship(index)}>
                                        <Trash2 className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}

                        <Button type="button" variant="outline" size="sm" onClick={addRelationship}>
                            <Plus className="mr-2 h-4 w-4" />
                            Anadir relacion
                        </Button>
                    </section>

                    <Separator />

                    {/* Notes */}
                    <section className="space-y-4">
                        <HeadingSmall title="Notas" />
                        <div className="grid gap-2">
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="Informacion adicional sobre el contacto..."
                                rows={3}
                            />
                            <InputError message={errors.notes} />
                        </div>
                    </section>

                    {/* Duplicate Acknowledgment */}
                    {(duplicateWarnings.email || duplicateWarnings.phone) && (
                        <>
                            <Separator />
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
                        </>
                    )}

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Button type="button" variant="outline" onClick={() => router.visit('/contacts')} disabled={processing}>
                            Cancelar
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || (((duplicateWarnings.email || duplicateWarnings.phone) && !duplicatesAcknowledged) as boolean)}
                        >
                            <Save className="mr-2 h-4 w-4" />
                            {processing ? 'Guardando...' : 'Guardar contacto'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
