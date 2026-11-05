import { Link } from '@inertiajs/react';
import { FileSignature, Printer, Upload } from 'lucide-react';
import { useState } from 'react';

import DownloadInformedConsentController from '@/actions/App/Http/Controllers/DownloadInformedConsentController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

type Signer = 'patient' | 'representative';

interface Props {
    patient: { id: number; name: string; birth_date?: string | null };
    prescriptionId?: number;
    onUploadSignedCopy?: () => void;
}

const isMinor = (birthDate?: string | null): boolean => {
    if (!birthDate) {
        return false;
    }

    const adulthood = new Date(birthDate);
    adulthood.setFullYear(adulthood.getFullYear() + 18);

    return adulthood > new Date();
};

export function InformedConsentDialog({ patient, prescriptionId, onUploadSignedCopy }: Props) {
    const [open, setOpen] = useState(false);
    const [signer, setSigner] = useState<Signer>(isMinor(patient.birth_date) ? 'representative' : 'patient');
    const [representativeName, setRepresentativeName] = useState('');
    const [representativeDocumentType, setRepresentativeDocumentType] = useState<'cedula' | 'pasaporte' | ''>('');
    const [representativeDocumentNumber, setRepresentativeDocumentNumber] = useState('');

    const handlePrint = (): void => {
        const query: Record<string, string | number> = { signer };

        if (prescriptionId) {
            query.prescription_id = prescriptionId;
        }

        if (signer === 'representative') {
            if (representativeName.trim()) query.representative_name = representativeName.trim();
            if (representativeDocumentType) query.representative_document_type = representativeDocumentType;
            if (representativeDocumentNumber.trim()) query.representative_document_number = representativeDocumentNumber.trim();
        }

        window.open(DownloadInformedConsentController.url(patient.id, { query }), '_blank', 'noopener');
    };

    return (
        <>
            <Button variant="outline" size="sm" onClick={() => setOpen(true)}>
                <FileSignature className="mr-2 h-4 w-4" />
                Consentimiento informado
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Consentimiento informado</DialogTitle>
                        <DialogDescription>
                            Se imprime el formato CL-OPT-001 con los datos de {patient.name}. Los procedimientos, la autorización y las firmas se
                            llenan a mano.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label>¿Quién firma?</Label>
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                value={signer}
                                onValueChange={(value) => value && setSigner(value as Signer)}
                                className="w-full"
                            >
                                <ToggleGroupItem value="patient" className="flex-1 data-[state=on]:bg-primary data-[state=on]:text-primary-foreground">
                                    El paciente
                                </ToggleGroupItem>
                                <ToggleGroupItem value="representative" className="flex-1 data-[state=on]:bg-primary data-[state=on]:text-primary-foreground">
                                    Representante legal
                                </ToggleGroupItem>
                            </ToggleGroup>
                            {isMinor(patient.birth_date) && <p className="text-xs text-amber-700">El paciente es menor de edad.</p>}
                        </div>

                        {signer === 'representative' && (
                            <div className="space-y-3 rounded-lg border p-3">
                                <p className="text-xs text-muted-foreground">Opcional: si lo dejas vacío, se llena a mano en el formato.</p>
                                <div className="space-y-1">
                                    <Label htmlFor="representative_name">Nombre del representante legal</Label>
                                    <Input
                                        id="representative_name"
                                        value={representativeName}
                                        onChange={(e) => setRepresentativeName(e.target.value)}
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="space-y-1">
                                        <Label>Documento</Label>
                                        <Select
                                            value={representativeDocumentType}
                                            onValueChange={(value) => setRepresentativeDocumentType(value as 'cedula' | 'pasaporte')}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cedula">Cédula</SelectItem>
                                                <SelectItem value="pasaporte">Pasaporte</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-1">
                                        <Label htmlFor="representative_document_number">Número</Label>
                                        <Input
                                            id="representative_document_number"
                                            value={representativeDocumentNumber}
                                            onChange={(e) => setRepresentativeDocumentNumber(e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex items-start gap-2 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                            <Upload className="mt-0.5 h-4 w-4 shrink-0" />
                            <p>
                                Después de firmarlo, sube la copia escaneada en{' '}
                                {onUploadSignedCopy ? (
                                    <button
                                        type="button"
                                        className="font-medium text-primary underline"
                                        onClick={() => {
                                            setOpen(false);
                                            onUploadSignedCopy();
                                        }}
                                    >
                                        Documentos del paciente
                                    </button>
                                ) : (
                                    <Link href={`/contacts/${patient.id}?tab=documents`} className="font-medium text-primary underline">
                                        Documentos del paciente
                                    </Link>
                                )}
                                .
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>
                            Cancelar
                        </Button>
                        <Button onClick={handlePrint}>
                            <Printer className="mr-2 h-4 w-4" />
                            Imprimir
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
