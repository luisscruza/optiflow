import { Plus, Building2, Save } from 'lucide-react';
import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import QuickContactModal from '@/components/contacts/quick-contact-modal';
import ClinicalHistory from '@/components/prescriptions/clinical-history';
import LensometriaAgudeza from '@/components/prescriptions/lensometria-agudeza';
import BiomicroscopiaModal from '@/components/prescriptions/biomicroscopia-modal';
import OftalmoscopiaModal from '@/components/prescriptions/oftalmoscopia-modal';
import QueratometriaPresion from '@/components/prescriptions/queratometria-presion';
import { MasterTableData, type Contact, type Workspace } from '@/types';

interface PrescriptionFormData {
    workspace_id: number | null;
    contact_id: number | null;
    optometrist_id: number | null;
    motivos_consulta: number[];
    estado_salud_actual: number[];
    historia_ocular_familiar: number[];

    // Lensometría
    lensometria_od?: string;
    lensometria_oi?: string;
    lensometria_add?: string;

    // Agudeza Visual - Lejana
    av_lejos_sc_od?: string;
    av_lejos_sc_oi?: string;
    av_lejos_cc_od?: string;
    av_lejos_cc_oi?: string;
    av_lejos_ph_od?: string;
    av_lejos_ph_oi?: string;

    // Agudeza Visual - Cercana
    av_cerca_sc_od?: string;
    av_cerca_sc_oi?: string;
    av_cerca_cc_od?: string;
    av_cerca_cc_oi?: string;
    av_cerca_ph_od?: string;
    av_cerca_ph_oi?: string;

    // Biomicroscopía - OD
    biomicroscopia_od_cejas?: string;
    biomicroscopia_od_pestanas?: string;
    biomicroscopia_od_parpados?: string;
    biomicroscopia_od_conjuntiva?: string;
    biomicroscopia_od_esclerotica?: string;
    biomicroscopia_od_cornea?: string;
    biomicroscopia_od_iris?: string;
    biomicroscopia_od_pupila?: string;
    biomicroscopia_od_cristalino?: string;

    // Biomicroscopía - OI
    biomicroscopia_oi_cejas?: string;
    biomicroscopia_oi_pestanas?: string;
    biomicroscopia_oi_parpados?: string;
    biomicroscopia_oi_conjuntiva?: string;
    biomicroscopia_oi_esclerotica?: string;
    biomicroscopia_oi_cornea?: string;
    biomicroscopia_oi_iris?: string;
    biomicroscopia_oi_pupila?: string;
    biomicroscopia_oi_cristalino?: string;

    // Biomicroscopía - Observaciones
    biomicroscopia_observaciones?: string;

    // Examen Pupilar - OD
    pupilar_od_fotomotor_directo?: string;
    pupilar_od_consensual?: string;
    pupilar_od_acomodativo?: string;

    // Examen Pupilar - OI
    pupilar_oi_fotomotor_directo?: string;
    pupilar_oi_consensual?: string;
    pupilar_oi_acomodativo?: string;

    // Oftalmoscopía - OD
    oftalmoscopia_od_color?: string;
    oftalmoscopia_od_papila?: string;
    oftalmoscopia_od_excavacion?: string;
    oftalmoscopia_od_relacion_av?: string;
    oftalmoscopia_od_macula?: string;
    oftalmoscopia_od_brillo_foveal?: string;
    oftalmoscopia_od_fijacion?: string;

    // Oftalmoscopía - OI
    oftalmoscopia_oi_color?: string;
    oftalmoscopia_oi_papila?: string;
    oftalmoscopia_oi_excavacion?: string;
    oftalmoscopia_oi_relacion_av?: string;
    oftalmoscopia_oi_macula?: string;
    oftalmoscopia_oi_brillo_foveal?: string;
    oftalmoscopia_oi_fijacion?: string;

    // Oftalmoscopía - Observaciones
    oftalmoscopia_observaciones?: string;

    // Queratometría - OD
    quera_od_horizontal?: string;
    quera_od_vertical?: string;
    quera_od_eje?: string;
    quera_od_dif?: string;

    // Queratometría - OI
    quera_oi_horizontal?: string;
    quera_oi_vertical?: string;
    quera_oi_eje?: string;
    quera_oi_dif?: string;

    // Presión Intraocular - OD
    presion_od?: string;
    presion_od_hora?: string;

    // Presión Intraocular - OI
    presion_oi?: string;
    presion_oi_hora?: string;

    // Observaciones
    observaciones?: string;
}

interface PrescriptionFormProps {
    customers: Contact[];
    optometrists: Contact[];
    masterTables: Record<string, MasterTableData>;
    workspace: { current: Workspace | null; available: Workspace[] };
    initialData?: Partial<PrescriptionFormData>;
    submitUrl: string;
    redirectUrl: string;
    submitButtonText?: string;
    isEditing?: boolean;
}

export default function PrescriptionForm({
    customers,
    optometrists,
    masterTables,
    workspace,
    initialData = {},
    submitUrl,
    redirectUrl,
    submitButtonText = 'Guardar receta',
    isEditing = false
}: PrescriptionFormProps) {
    const [showContactModal, setShowContactModal] = useState(false);
    const [contactsList, setContactsList] = useState<Contact[]>(customers);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(
        initialData.contact_id ? customers.find(c => c.id === initialData.contact_id) || null : null
    );
    const [selectedOptometrist, setSelectedOptometrist] = useState<Contact | null>(
        initialData.optometrist_id ? optometrists.find(o => o.id === initialData.optometrist_id) || null : null
    );

    if (!workspace || !workspace.available?.length) {
        return null;
    }

    const { current, available } = workspace;

    const { data, setData, post, put, processing, errors } = useForm<PrescriptionFormData>({
        contact_id: initialData.contact_id || null,
        workspace_id: initialData.workspace_id || current?.id || null,
        optometrist_id: initialData.optometrist_id || null,
        motivos_consulta: initialData.motivos_consulta || [],
        estado_salud_actual: initialData.estado_salud_actual || [],
        historia_ocular_familiar: initialData.historia_ocular_familiar || [],

        // Lensometría
        lensometria_od: initialData.lensometria_od || '',
        lensometria_oi: initialData.lensometria_oi || '',
        lensometria_add: initialData.lensometria_add || '',

        // Agudeza Visual - Lejana
        av_lejos_sc_od: initialData.av_lejos_sc_od || '',
        av_lejos_sc_oi: initialData.av_lejos_sc_oi || '',
        av_lejos_cc_od: initialData.av_lejos_cc_od || '',
        av_lejos_cc_oi: initialData.av_lejos_cc_oi || '',
        av_lejos_ph_od: initialData.av_lejos_ph_od || '',
        av_lejos_ph_oi: initialData.av_lejos_ph_oi || '',

        // Agudeza Visual - Cercana
        av_cerca_sc_od: initialData.av_cerca_sc_od || '',
        av_cerca_sc_oi: initialData.av_cerca_sc_oi || '',
        av_cerca_cc_od: initialData.av_cerca_cc_od || '',
        av_cerca_cc_oi: initialData.av_cerca_cc_oi || '',
        av_cerca_ph_od: initialData.av_cerca_ph_od || '',
        av_cerca_ph_oi: initialData.av_cerca_ph_oi || '',

        // Biomicroscopía - OD
        biomicroscopia_od_cejas: initialData.biomicroscopia_od_cejas || '',
        biomicroscopia_od_pestanas: initialData.biomicroscopia_od_pestanas || '',
        biomicroscopia_od_parpados: initialData.biomicroscopia_od_parpados || '',
        biomicroscopia_od_conjuntiva: initialData.biomicroscopia_od_conjuntiva || '',
        biomicroscopia_od_esclerotica: initialData.biomicroscopia_od_esclerotica || '',
        biomicroscopia_od_cornea: initialData.biomicroscopia_od_cornea || '',
        biomicroscopia_od_iris: initialData.biomicroscopia_od_iris || '',
        biomicroscopia_od_pupila: initialData.biomicroscopia_od_pupila || '',
        biomicroscopia_od_cristalino: initialData.biomicroscopia_od_cristalino || '',

        // Biomicroscopía - OI
        biomicroscopia_oi_cejas: initialData.biomicroscopia_oi_cejas || '',
        biomicroscopia_oi_pestanas: initialData.biomicroscopia_oi_pestanas || '',
        biomicroscopia_oi_parpados: initialData.biomicroscopia_oi_parpados || '',
        biomicroscopia_oi_conjuntiva: initialData.biomicroscopia_oi_conjuntiva || '',
        biomicroscopia_oi_esclerotica: initialData.biomicroscopia_oi_esclerotica || '',
        biomicroscopia_oi_cornea: initialData.biomicroscopia_oi_cornea || '',
        biomicroscopia_oi_iris: initialData.biomicroscopia_oi_iris || '',
        biomicroscopia_oi_pupila: initialData.biomicroscopia_oi_pupila || '',
        biomicroscopia_oi_cristalino: initialData.biomicroscopia_oi_cristalino || '',

        // Biomicroscopía - Observaciones
        biomicroscopia_observaciones: initialData.biomicroscopia_observaciones || '',

        // Examen Pupilar - OD
        pupilar_od_fotomotor_directo: initialData.pupilar_od_fotomotor_directo || '',
        pupilar_od_consensual: initialData.pupilar_od_consensual || '',
        pupilar_od_acomodativo: initialData.pupilar_od_acomodativo || '',

        // Examen Pupilar - OI
        pupilar_oi_fotomotor_directo: initialData.pupilar_oi_fotomotor_directo || '',
        pupilar_oi_consensual: initialData.pupilar_oi_consensual || '',
        pupilar_oi_acomodativo: initialData.pupilar_oi_acomodativo || '',

        // Oftalmoscopía - OD
        oftalmoscopia_od_color: initialData.oftalmoscopia_od_color || '',
        oftalmoscopia_od_papila: initialData.oftalmoscopia_od_papila || '',
        oftalmoscopia_od_excavacion: initialData.oftalmoscopia_od_excavacion || '',
        oftalmoscopia_od_relacion_av: initialData.oftalmoscopia_od_relacion_av || '',
        oftalmoscopia_od_macula: initialData.oftalmoscopia_od_macula || '',
        oftalmoscopia_od_brillo_foveal: initialData.oftalmoscopia_od_brillo_foveal || '',
        oftalmoscopia_od_fijacion: initialData.oftalmoscopia_od_fijacion || '',

        // Oftalmoscopía - OI
        oftalmoscopia_oi_color: initialData.oftalmoscopia_oi_color || '',
        oftalmoscopia_oi_papila: initialData.oftalmoscopia_oi_papila || '',
        oftalmoscopia_oi_excavacion: initialData.oftalmoscopia_oi_excavacion || '',
        oftalmoscopia_oi_relacion_av: initialData.oftalmoscopia_oi_relacion_av || '',
        oftalmoscopia_oi_macula: initialData.oftalmoscopia_oi_macula || '',
        oftalmoscopia_oi_brillo_foveal: initialData.oftalmoscopia_oi_brillo_foveal || '',
        oftalmoscopia_oi_fijacion: initialData.oftalmoscopia_oi_fijacion || '',

        // Oftalmoscopía - Observaciones
        oftalmoscopia_observaciones: initialData.oftalmoscopia_observaciones || '',

        // Queratometría - OD
        quera_od_horizontal: initialData.quera_od_horizontal || '',
        quera_od_vertical: initialData.quera_od_vertical || '',
        quera_od_eje: initialData.quera_od_eje || '',
        quera_od_dif: initialData.quera_od_dif || '',

        // Queratometría - OI
        quera_oi_horizontal: initialData.quera_oi_horizontal || '',
        quera_oi_vertical: initialData.quera_oi_vertical || '',
        quera_oi_eje: initialData.quera_oi_eje || '',
        quera_oi_dif: initialData.quera_oi_dif || '',

        // Presión Intraocular - OD
        presion_od: initialData.presion_od || '',
        presion_od_hora: initialData.presion_od_hora || '',

        // Presión Intraocular - OI
        presion_oi: initialData.presion_oi || '',
        presion_oi_hora: initialData.presion_oi_hora || '',

        // Observaciones
        observaciones: initialData.observaciones || '',
    });

    const handleWorkspaceSwitch = (workspaceId: string) => {
        setData('workspace_id', parseInt(workspaceId));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const submitMethod = isEditing ? put : post;

        submitMethod(submitUrl, {
            onSuccess: () => {
                router.visit(redirectUrl);
            },
        });
    };

    const handleContactCreated = (newContact: Contact) => {
        setContactsList(prev => [...prev, newContact]);
        setData('contact_id', newContact.id);
        setSelectedContact(newContact);
    };

    const handleContactSelect = (contactId: string) => {
        const contact = contactsList.find(c => c.id === parseInt(contactId));
        setData('contact_id', parseInt(contactId));
        setSelectedContact(contact || null);
    };

    const contactOptions: SearchableSelectOption[] = contactsList.map((contact) => ({
        value: contact.id.toString(),
        label: contact.name,
    }));

    const isFormValid = data.contact_id && data.optometrist_id && data.workspace_id;

    return (
        <>
            <form id="prescription-form" onSubmit={handleSubmit} className="space-y-8">
                {/* Customer and Document Details - Three Column Layout */}
                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                    <CardContent className="px-6 py-6">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* First Column - Workspace Selection */}
                            <div className="space-y-6">
                                {/* Workspace Selection */}
                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                        <Building2 className="h-4 w-4" />
                                        Sucursal
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={data.workspace_id?.toString() || ''}
                                        onValueChange={handleWorkspaceSwitch}
                                    >
                                        <SelectTrigger className={`h-10 ${errors.workspace_id ? 'border-red-300 ring-red-500/20' : 'border-gray-300'}`}>
                                            <SelectValue placeholder="Seleccionar espacio de trabajo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {available.map((workspace) => (
                                                <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                    {workspace.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.workspace_id && (
                                        <p className="text-sm text-red-600">{errors.workspace_id}</p>
                                    )}
                                </div>
                            </div>

                            {/* Second Column - Contact Selection */}
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                        Contacto
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <div className="flex gap-2">
                                        <SearchableSelect
                                            options={contactOptions}
                                            value={data.contact_id?.toString() || ''}
                                            onValueChange={handleContactSelect}
                                            placeholder="Buscar contacto..."
                                            searchPlaceholder="Escribir para buscar..."
                                            emptyText="No se encontró ningún contacto."
                                            noEmptyAction={
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setShowContactModal(true)}
                                                    className="text-primary hover:bg-primary/10"
                                                >
                                                    <Plus className="h-4 w-4 mr-1" />
                                                    Crear nuevo contacto
                                                </Button>
                                            }
                                            className="flex-1"
                                            triggerClassName={`h-10 ${errors.contact_id ? 'border-red-300 ring-red-500/20' : 'border-gray-300'}`}
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setShowContactModal(true)}
                                            className="h-10 px-3 border-gray-300 text-primary hover:bg-primary/10"
                                        >
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </div>
                                    {errors.contact_id && (
                                        <p className="text-sm text-red-600">{errors.contact_id}</p>
                                    )}
                                </div>
                            </div>

                            {/* Third Column - Optometrist Selection */}
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                        Optómetra
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <SearchableSelect
                                        options={optometrists.map((optometrist) => ({
                                            value: optometrist.id.toString(),
                                            label: optometrist.name,
                                        }))}
                                        value={data.optometrist_id?.toString() || ''}
                                        onValueChange={(value) => {
                                            const optometrist = optometrists.find(o => o.id === parseInt(value));
                                            setData('optometrist_id', parseInt(value));
                                            setSelectedOptometrist(optometrist || null);
                                        }}
                                        placeholder="Buscar optometra..."
                                        searchPlaceholder="Escribir para buscar..."
                                        emptyText="No se encontró ningún optómetra."
                                        className="flex-1"
                                        triggerClassName={`h-10 ${errors.optometrist_id ? 'border-red-300 ring-red-500/20' : 'border-gray-300'}`}
                                    />
                                    {errors.optometrist_id && (
                                        <p className="text-sm text-red-600">{errors.optometrist_id}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Historial clínico */}
                <ClinicalHistory
                    masterTables={masterTables}
                    data={{
                        motivos_consulta: data.motivos_consulta,
                        estado_salud_actual: data.estado_salud_actual,
                        historia_ocular_familiar: data.historia_ocular_familiar,
                    }}
                    onDataChange={(field, value) => setData(field, value)}
                    errors={{
                        motivos_consulta: errors.motivos_consulta,
                        estado_salud_actual: errors.estado_salud_actual,
                        historia_ocular_familiar: errors.historia_ocular_familiar,
                    }}
                />

                {/* Lensometría, Examen Externo y Oftalmoscopía - Three Column Layout */}
                <Card className="border-0 bg-white shadow-sm ring-1 ring-gray-950/5">
                    <CardContent className="p-4 flex flex-col justify-start">
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            {/* Left Side - Lensometría y Agudeza Visual */}
                            <div className="lg:col-span-1 flex flex-col">
                                <LensometriaAgudeza
                                    data={{
                                        lensometria_od: data.lensometria_od,
                                        lensometria_oi: data.lensometria_oi,
                                        lensometria_add: data.lensometria_add,
                                        av_lejos_sc_od: data.av_lejos_sc_od,
                                        av_lejos_sc_oi: data.av_lejos_sc_oi,
                                        av_lejos_cc_od: data.av_lejos_cc_od,
                                        av_lejos_cc_oi: data.av_lejos_cc_oi,
                                        av_lejos_ph_od: data.av_lejos_ph_od,
                                        av_lejos_ph_oi: data.av_lejos_ph_oi,
                                        av_cerca_sc_od: data.av_cerca_sc_od,
                                        av_cerca_sc_oi: data.av_cerca_sc_oi,
                                        av_cerca_cc_od: data.av_cerca_cc_od,
                                        av_cerca_cc_oi: data.av_cerca_cc_oi,
                                        av_cerca_ph_od: data.av_cerca_ph_od,
                                        av_cerca_ph_oi: data.av_cerca_ph_oi,
                                        observaciones: data.observaciones,
                                    }}
                                    onChange={(field, value) => setData(field as any, value)}
                                    errors={errors}
                                />
                            </div>

                            {/* Middle - Examen Externo/Biomicroscopía */}
                            <div className="lg:col-span-1 flex flex-col">
                                <BiomicroscopiaModal
                                    data={{
                                        biomicroscopia_od_cejas: data.biomicroscopia_od_cejas,
                                        biomicroscopia_od_pestanas: data.biomicroscopia_od_pestanas,
                                        biomicroscopia_od_parpados: data.biomicroscopia_od_parpados,
                                        biomicroscopia_od_conjuntiva: data.biomicroscopia_od_conjuntiva,
                                        biomicroscopia_od_esclerotica: data.biomicroscopia_od_esclerotica,
                                        biomicroscopia_od_cornea: data.biomicroscopia_od_cornea,
                                        biomicroscopia_od_iris: data.biomicroscopia_od_iris,
                                        biomicroscopia_od_pupila: data.biomicroscopia_od_pupila,
                                        biomicroscopia_od_cristalino: data.biomicroscopia_od_cristalino,
                                        biomicroscopia_oi_cejas: data.biomicroscopia_oi_cejas,
                                        biomicroscopia_oi_pestanas: data.biomicroscopia_oi_pestanas,
                                        biomicroscopia_oi_parpados: data.biomicroscopia_oi_parpados,
                                        biomicroscopia_oi_conjuntiva: data.biomicroscopia_oi_conjuntiva,
                                        biomicroscopia_oi_esclerotica: data.biomicroscopia_oi_esclerotica,
                                        biomicroscopia_oi_cornea: data.biomicroscopia_oi_cornea,
                                        biomicroscopia_oi_iris: data.biomicroscopia_oi_iris,
                                        biomicroscopia_oi_pupila: data.biomicroscopia_oi_pupila,
                                        biomicroscopia_oi_cristalino: data.biomicroscopia_oi_cristalino,
                                        biomicroscopia_observaciones: data.biomicroscopia_observaciones,
                                    }}
                                    onChange={(field, value) => setData(field as any, value)}
                                    errors={errors}
                                />
                            </div>

                            {/* Right Side - Oftalmoscopía */}
                            <div className="lg:col-span-1 flex flex-col">
                                <OftalmoscopiaModal
                                    data={{
                                        pupilar_od_fotomotor_directo: data.pupilar_od_fotomotor_directo,
                                        pupilar_od_consensual: data.pupilar_od_consensual,
                                        pupilar_od_acomodativo: data.pupilar_od_acomodativo,
                                        pupilar_oi_fotomotor_directo: data.pupilar_oi_fotomotor_directo,
                                        pupilar_oi_consensual: data.pupilar_oi_consensual,
                                        pupilar_oi_acomodativo: data.pupilar_oi_acomodativo,
                                        oftalmoscopia_od_color: data.oftalmoscopia_od_color,
                                        oftalmoscopia_od_papila: data.oftalmoscopia_od_papila,
                                        oftalmoscopia_od_excavacion: data.oftalmoscopia_od_excavacion,
                                        oftalmoscopia_od_relacion_av: data.oftalmoscopia_od_relacion_av,
                                        oftalmoscopia_od_macula: data.oftalmoscopia_od_macula,
                                        oftalmoscopia_od_brillo_foveal: data.oftalmoscopia_od_brillo_foveal,
                                        oftalmoscopia_od_fijacion: data.oftalmoscopia_od_fijacion,
                                        oftalmoscopia_oi_color: data.oftalmoscopia_oi_color,
                                        oftalmoscopia_oi_papila: data.oftalmoscopia_oi_papila,
                                        oftalmoscopia_oi_excavacion: data.oftalmoscopia_oi_excavacion,
                                        oftalmoscopia_oi_relacion_av: data.oftalmoscopia_oi_relacion_av,
                                        oftalmoscopia_oi_macula: data.oftalmoscopia_oi_macula,
                                        oftalmoscopia_oi_brillo_foveal: data.oftalmoscopia_oi_brillo_foveal,
                                        oftalmoscopia_oi_fijacion: data.oftalmoscopia_oi_fijacion,
                                        oftalmoscopia_observaciones: data.oftalmoscopia_observaciones,
                                    }}
                                    onChange={(field, value) => setData(field as any, value)}
                                    errors={errors}
                                />
                            </div>
                        </div>

                        {/* Queratometría y Presión Intraocular - Bottom Row */}
                        <div className="mt-4 flex gap-2">
                            <QueratometriaPresion
                                data={{
                                    quera_od_horizontal: data.quera_od_horizontal,
                                    quera_od_vertical: data.quera_od_vertical,
                                    quera_od_eje: data.quera_od_eje,
                                    quera_od_dif: data.quera_od_dif,
                                    quera_oi_horizontal: data.quera_oi_horizontal,
                                    quera_oi_vertical: data.quera_oi_vertical,
                                    quera_oi_eje: data.quera_oi_eje,
                                    quera_oi_dif: data.quera_oi_dif,
                                    presion_od: data.presion_od,
                                    presion_od_hora: data.presion_od_hora,
                                    presion_oi: data.presion_oi,
                                    presion_oi_hora: data.presion_oi_hora,
                                }}
                                onChange={(field, value) => setData(field as any, value)}
                                errors={errors}
                            />
                           
                        </div>
                    </CardContent>
                </Card>
            </form>

            <QuickContactModal
                open={showContactModal}
                onOpenChange={setShowContactModal}
                onSuccess={handleContactCreated}
                types={['customer']}
                onAdvancedForm={() => {
                    router.visit('/contacts/create');
                }}
            />

            {/* Floating Submit Button */}
            <div className="fixed bottom-6 right-6 z-50">
                <Button
                    type="submit"
                    form="prescription-form"
                    size="lg"
                    disabled={processing || !isFormValid}
                    className="flex bg-blue-600 hover:bg-blue-700 shadow-lg hover:shadow-xl transition-all duration-200 group disabled:bg-gray-400 disabled:cursor-not-allowed"
                    onClick={handleSubmit}
                >
                    {processing ? (
                        <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></div>
                    ) : (
                        <>
                            <Save className="h-5 w-5 mr-2" />
                            {submitButtonText}
                        </>
                    )}
                </Button>
                {/* Tooltip */}
                <div className="absolute bottom-16 right-0 bg-gray-900 text-white text-xs px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg">
                    {!isFormValid
                        ? 'Complete los campos requeridos'
                        : processing
                            ? 'Guardando...'
                            : submitButtonText
                    }
                </div>
            </div>
        </>
    );
}