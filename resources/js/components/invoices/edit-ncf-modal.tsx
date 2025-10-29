import { AlertCircle, Settings } from 'lucide-react';
import { useState } from 'react';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface EditNcfModalProps {
    isOpen: boolean;
    onClose: () => void;
    currentNcf: string;
    prefix: string;
    nextNumber: number;
    onSave: (newNcf: string) => void;
    invoiceId?: number;
}

export function EditNcfModal({ isOpen, onClose, currentNcf, prefix, nextNumber, onSave, invoiceId }: EditNcfModalProps) {
    // Extract only the numeric part from current NCF
    const currentNumber = currentNcf.substring(prefix.length);
    const [number, setNumber] = useState(currentNumber);
    const [validating, setValidating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleValidateAndSave = async () => {
        setError(null);
        setValidating(true);

        try {
            // Validate number is valid
            const ncfNumber = parseInt(number);

            if (isNaN(ncfNumber)) {
                setError('Debes ingresar un número válido');
                setValidating(false);
                return;
            }

            // Validate against next_number
            if (ncfNumber < nextNumber) {
                setError(`El número no puede ser menor que el próximo disponible (${String(nextNumber).padStart(8, '0')})`);
                setValidating(false);
                return;
            }

            // Build complete NCF
            const fullNcf = prefix + number.padStart(8, '0');

            // Call backend validation
            const response = await fetch('/api/invoices/validate-ncf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    ncf: fullNcf,
                    invoice_id: invoiceId,
                }),
            });

            const result = await response.json();

            if (!response.ok || !result.valid) {
                setError(result.message || 'El NCF no es válido');
                setValidating(false);
                return;
            }

            // If all validations pass, save
            onSave(fullNcf);
            onClose();
        } catch (err) {
            setError('Error al validar el NCF. Por favor, intenta de nuevo.');
            console.error('NCF validation error:', err);
        } finally {
            setValidating(false);
        }
    };

    const handleClose = () => {
        setNumber(currentNumber);
        setError(null);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <Settings className="h-4 w-4" />
                        </div>
                        Editar NCF manualmente
                    </DialogTitle>
                    <DialogDescription>
                        Puedes editar solo la numeración del NCF. El número debe ser igual o mayor al próximo disponible y no puede estar duplicado.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div className="space-y-2">
                        <Label htmlFor="ncf-number" className="text-sm font-semibold text-gray-900">
                            NCF
                        </Label>
                        <div className="flex items-center gap-2">
                            <div className="flex items-center rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-base text-gray-700">
                                {prefix}
                            </div>
                            <Input
                                id="ncf-number"
                                value={number}
                                onChange={(e) => {
                                    // Only allow numbers
                                    const value = e.target.value.replace(/\D/g, '');
                                    setNumber(value);
                                    setError(null);
                                }}
                                placeholder="00000000"
                                className="flex-1 font-mono text-base"
                                maxLength={8}
                            />
                        </div>
                        <p className="text-xs text-gray-500">
                            Próximo disponible: <span className="font-mono font-semibold">{String(nextNumber).padStart(8, '0')}</span>
                        </p>
                    </div>

                    {error && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}
                </div>

                <DialogFooter className="gap-2 sm:gap-0">
                    <Button type="button" variant="outline" onClick={handleClose}>
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        onClick={handleValidateAndSave}
                        disabled={validating || !number || number === currentNumber}
                        className="ml-2 bg-blue-600 hover:bg-blue-700"
                    >
                        {validating ? (
                            <>
                                <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                Validando...
                            </>
                        ) : (
                            'Guardar'
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
