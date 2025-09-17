import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, ChevronDown, ChevronRight, Save, Users } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type ContactType, type IdentificationType } from '@/types';
import countriesData from '@/data/countries.json';

interface Props {
    contact: Contact;
    contact_types: Array<{ value: ContactType; label: string }>;
    identification_types: Array<{ value: IdentificationType; label: string }>;
}

interface ContactFormData {
    contact_type: ContactType;
    name: string;
    status: 'active' | 'inactive';
    email: string;
    phone_primary: string;
    phone_secondary: string;
    website: string;
    notes: string;
    identification_type: IdentificationType | undefined;
    identification_number: string;
    address_line_1: string;
    address_line_2: string;
    municipality: string;
    province: string;
    postal_code: string;
    country: string;
}

export default function EditContact({ contact, contact_types, identification_types }: Props) {
    const [basicInfoOpen, setBasicInfoOpen] = useState(true);
    const [contactInfoOpen, setContactInfoOpen] = useState(true);
    const [identificationOpen, setIdentificationOpen] = useState(false);
    const [addressOpen, setAddressOpen] = useState(false);
    const [additionalOpen, setAdditionalOpen] = useState(false);

    // Get available countries and their provinces/cities
    const countries = Object.keys(countriesData);
    
    // Get provinces/cities for the selected country
    const getProvincesForCountry = (country: string) => {
        return countriesData[country as keyof typeof countriesData] || [];
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Contactos',
            href: '/contacts',
        },
        {
            title: contact.name,
            href: `/contacts/${contact.id}`,
        },
        {
            title: 'Editar',
            href: `/contacts/${contact.id}/edit`,
        },
    ];

    // Pre-populate form with existing contact data
    const { data, setData, put, processing, errors } = useForm<ContactFormData>({
        contact_type: contact.contact_type,
        name: contact.name,
        status: contact.status,
        email: contact.email || '',
        phone_primary: contact.phone_primary || '',
        phone_secondary: contact.phone_secondary || '',
        website: '', // Contact model doesn't have website field, keeping for future
        notes: contact.observations || '',
        identification_type: (contact.identification_type as IdentificationType) || undefined,
        identification_number: contact.identification_number || '',
        address_line_1: contact.primary_address?.description || '',
        address_line_2: '',
        municipality: contact.primary_address?.municipality || '',
        province: contact.primary_address?.province || '',
        postal_code: '',
        country: contact.primary_address?.country || 'Dominican Republic',
    });
    
    const selectedCountryProvinces = getProvincesForCountry(data.country);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/contacts/${contact.id}`);
    };

    const handleCountryChange = (country: string) => {
        setData('country', country);
        // Clear municipality when country changes since provinces will be different
        setData('municipality', '');
    };

    const getContactTypeIcon = (type: ContactType) => {
        if (type === 'customer') return <Users className="h-4 w-4" />;
        if (type === 'supplier') return <Building2 className="h-4 w-4" />;
        return null;
    };

    const getContactTypeDescription = (type: ContactType) => {
        if (type === 'customer') return 'Clientes que compran productos o servicios';
        if (type === 'supplier') return 'Proveedores de productos o servicios';
        return 'Selecciona el tipo de contacto';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${contact.name}`} />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="">
                    <div className="mb-8 flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Editar contacto</h1>
                            <p className="text-gray-600 dark:text-gray-400">Actualiza la información de {contact.name}</p>
                        </div>

                        <Button variant="outline" onClick={() => router.visit(`/contacts/${contact.id}`)}>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </div>

                    {/* Required Fields Notice */}
                    <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <p className="text-sm text-blue-800 dark:text-blue-200">Los campos marcados con asterisco (*) son obligatorios.</p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Contact Type Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center space-x-2">
                                    {getContactTypeIcon(data.contact_type)}
                                    <span>Tipo de Contacto (*)</span>
                                </CardTitle>
                                <CardDescription>{getContactTypeDescription(data.contact_type)}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    {contact_types.map((type) => (
                                        <div
                                            key={type.value}
                                            className={`cursor-pointer rounded-lg border p-4 transition-colors ${
                                                data.contact_type === type.value
                                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                                            }`}
                                            onClick={() => setData('contact_type', type.value)}
                                        >
                                            <div className="flex items-center space-x-3">
                                                {getContactTypeIcon(type.value)}
                                                <div>
                                                    <div className="font-medium">{type.label}</div>
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">
                                                        {getContactTypeDescription(type.value)}
                                                    </div>
                                                </div>
                                                {data.contact_type === type.value && (
                                                    <Badge variant="default" className="ml-auto">
                                                        Seleccionado
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                {errors.contact_type && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.contact_type}</p>}
                            </CardContent>
                        </Card>

                        {/* Basic Information - Always Open */}
                        <Collapsible open={basicInfoOpen} onOpenChange={setBasicInfoOpen}>
                            <Card>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <CardTitle className="flex items-center justify-between">
                                            <span>Información Básica</span>
                                            {basicInfoOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                        </CardTitle>
                                        <CardDescription>Información fundamental del contacto</CardDescription>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="name">Nombre (*)</Label>
                                                <Input
                                                    id="name"
                                                    type="text"
                                                    value={data.name}
                                                    onChange={(e) => setData('name', e.target.value)}
                                                    placeholder="Nombre completo o razón social"
                                                    className={errors.name ? 'border-red-500' : ''}
                                                />
                                                {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="status">Estado (*)</Label>
                                                <Select
                                                    value={data.status}
                                                    onValueChange={(value: 'active' | 'inactive') => setData('status', value)}
                                                >
                                                    <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder="Selecciona el estado" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="active">Activo</SelectItem>
                                                        <SelectItem value="inactive">Inactivo</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                {errors.status && <p className="text-sm text-red-600 dark:text-red-400">{errors.status}</p>}
                                            </div>
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Contact Information - Always Open */}
                        <Collapsible open={contactInfoOpen} onOpenChange={setContactInfoOpen}>
                            <Card>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <CardTitle className="flex items-center justify-between">
                                            <span>Información de Contacto</span>
                                            {contactInfoOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                        </CardTitle>
                                        <CardDescription>Formas de comunicarse con el contacto</CardDescription>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    placeholder="correo@ejemplo.com"
                                                    className={errors.email ? 'border-red-500' : ''}
                                                />
                                                {errors.email && <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="phone_primary">Teléfono Principal</Label>
                                                <Input
                                                    id="phone_primary"
                                                    type="tel"
                                                    value={data.phone_primary}
                                                    onChange={(e) => setData('phone_primary', e.target.value)}
                                                    placeholder="+57 300 123 4567"
                                                    className={errors.phone_primary ? 'border-red-500' : ''}
                                                />
                                                {errors.phone_primary && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.phone_primary}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="phone_secondary">Teléfono Secundario</Label>
                                                <Input
                                                    id="phone_secondary"
                                                    type="tel"
                                                    value={data.phone_secondary}
                                                    onChange={(e) => setData('phone_secondary', e.target.value)}
                                                    placeholder="+57 300 123 4567"
                                                    className={errors.phone_secondary ? 'border-red-500' : ''}
                                                />
                                                {errors.phone_secondary && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.phone_secondary}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="website">Sitio Web</Label>
                                                <Input
                                                    id="website"
                                                    type="url"
                                                    value={data.website}
                                                    onChange={(e) => setData('website', e.target.value)}
                                                    placeholder="https://ejemplo.com"
                                                    className={errors.website ? 'border-red-500' : ''}
                                                />
                                                {errors.website && <p className="text-sm text-red-600 dark:text-red-400">{errors.website}</p>}
                                            </div>
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Identification - Collapsed by Default */}
                        <Collapsible open={identificationOpen} onOpenChange={setIdentificationOpen}>
                            <Card>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <CardTitle className="flex items-center justify-between">
                                            <span>Identificación</span>
                                            {identificationOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                        </CardTitle>
                                        <CardDescription>Documentos de identificación del contacto</CardDescription>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="identification_type">Tipo de Identificación</Label>
                                                <Select
                                                    value={data.identification_type || ''}
                                                    onValueChange={(value: IdentificationType) => setData('identification_type', value)}
                                                >
                                                    <SelectTrigger className={errors.identification_type ? 'border-red-500' : ''}>
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
                                                {errors.identification_type && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.identification_type}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="identification_number">Número de Identificación</Label>
                                                <Input
                                                    id="identification_number"
                                                    type="text"
                                                    value={data.identification_number}
                                                    onChange={(e) => setData('identification_number', e.target.value)}
                                                    placeholder="123456789"
                                                    className={errors.identification_number ? 'border-red-500' : ''}
                                                />
                                                {errors.identification_number && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.identification_number}</p>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Address - Collapsed by Default */}
                        <Collapsible open={addressOpen} onOpenChange={setAddressOpen}>
                            <Card>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <CardTitle className="flex items-center justify-between">
                                            <span>Dirección</span>
                                            {addressOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                        </CardTitle>
                                        <CardDescription>Dirección física del contacto</CardDescription>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="address_line_1">Dirección Línea 1</Label>
                                                <Input
                                                    id="address_line_1"
                                                    type="text"
                                                    value={data.address_line_1}
                                                    onChange={(e) => setData('address_line_1', e.target.value)}
                                                    placeholder="Calle, carrera, avenida"
                                                    className={errors.address_line_1 ? 'border-red-500' : ''}
                                                />
                                                {errors.address_line_1 && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.address_line_1}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="address_line_2">Dirección Línea 2</Label>
                                                <Input
                                                    id="address_line_2"
                                                    type="text"
                                                    value={data.address_line_2}
                                                    onChange={(e) => setData('address_line_2', e.target.value)}
                                                    placeholder="Apartamento, oficina, etc."
                                                    className={errors.address_line_2 ? 'border-red-500' : ''}
                                                />
                                                {errors.address_line_2 && (
                                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.address_line_2}</p>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                                <div className="space-y-2">
                                                    <Label htmlFor="municipality">Provincia</Label>
                                                    <Select
                                                        value={data.municipality}
                                                        onValueChange={(value) => setData('municipality', value)}
                                                    >
                                                        <SelectTrigger className={errors.municipality ? 'border-red-500' : ''}>
                                                            <SelectValue placeholder="Seleccionar provincia" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {selectedCountryProvinces.map((province) => (
                                                                <SelectItem 
                                                                    key={province} 
                                                                    value={province.toLowerCase().replace(/\s+/g, '-')}
                                                                >
                                                                    {province}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    {errors.municipality && (
                                                        <p className="text-sm text-red-600 dark:text-red-400">{errors.municipality}</p>
                                                    )}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="province">Departamento</Label>
                                                    <Input
                                                        id="province"
                                                        type="text"
                                                        value={data.province}
                                                        onChange={(e) => setData('province', e.target.value)}
                                                        placeholder="Cundinamarca"
                                                        className={errors.province ? 'border-red-500' : ''}
                                                    />
                                                    {errors.province && <p className="text-sm text-red-600 dark:text-red-400">{errors.province}</p>}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="postal_code">Código Postal</Label>
                                                    <Input
                                                        id="postal_code"
                                                        type="text"
                                                        value={data.postal_code}
                                                        onChange={(e) => setData('postal_code', e.target.value)}
                                                        placeholder="110111"
                                                        className={errors.postal_code ? 'border-red-500' : ''}
                                                    />
                                                    {errors.postal_code && (
                                                        <p className="text-sm text-red-600 dark:text-red-400">{errors.postal_code}</p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="country">País</Label>
                                                <Select
                                                    value={data.country}
                                                    onValueChange={handleCountryChange}
                                                >
                                                    <SelectTrigger className={errors.country ? 'border-red-500' : ''}>
                                                        <SelectValue placeholder="Seleccionar país" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {countries.map((country) => (
                                                            <SelectItem key={country} value={country}>
                                                                {country}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                {errors.country && <p className="text-sm text-red-600 dark:text-red-400">{errors.country}</p>}
                                            </div>
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Additional Information - Collapsed by Default */}
                        <Collapsible open={additionalOpen} onOpenChange={setAdditionalOpen}>
                            <Card>
                                <CollapsibleTrigger asChild>
                                    <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <CardTitle className="flex items-center justify-between">
                                            <span>Información Adicional</span>
                                            {additionalOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                        </CardTitle>
                                        <CardDescription>Notas y comentarios adicionales</CardDescription>
                                    </CardHeader>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <CardContent className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="notes">Notas</Label>
                                            <Textarea
                                                id="notes"
                                                value={data.notes}
                                                onChange={(e) => setData('notes', e.target.value)}
                                                placeholder="Información adicional sobre el contacto..."
                                                rows={4}
                                                className={errors.notes ? 'border-red-500' : ''}
                                            />
                                            {errors.notes && <p className="text-sm text-red-600 dark:text-red-400">{errors.notes}</p>}
                                        </div>
                                    </CardContent>
                                </CollapsibleContent>
                            </Card>
                        </Collapsible>

                        {/* Submit Button */}
                        <div className="flex items-center justify-end space-x-4 pt-6">
                            <Button type="button" variant="outline" onClick={() => router.visit(`/contacts/${contact.id}`)} disabled={processing}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <>Guardando...</>
                                ) : (
                                    <>
                                        <Save className="mr-2 h-4 w-4" />
                                        Guardar Cambios
                                    </>
                                )}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
