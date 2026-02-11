import { FileText } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface CashRegisterCloseModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export function CashRegisterCloseModal({ isOpen, onClose }: CashRegisterCloseModalProps) {
    const today = new Date().toISOString().slice(0, 10);
    const [date, setDate] = useState<string>(today);
    const [isLoading, setIsLoading] = useState(false);

    const handleGenerate = () => {
        setIsLoading(true);
        const url = `/cash-register-close?date=${encodeURIComponent(date)}`;
        window.open(url, '_blank');
        setIsLoading(false);
        onClose();
    };

    const handleOpenChange = (open: boolean) => {
        if (!open) {
            onClose();
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Cierre de Caja</DialogTitle>
                    <DialogDescription>
                        Genera un reporte PDF con el resumen de todos los pagos recibidos en el dia seleccionado, desglosado por metodo de pago,
                        cuenta bancaria y cliente.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                    <div className="grid gap-2">
                        <Label htmlFor="cash-close-date">Fecha a consultar</Label>
                        <Input id="cash-close-date" type="date" value={date} onChange={(e) => setDate(e.target.value)} max={today} />
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button onClick={handleGenerate} disabled={!date || isLoading}>
                        <FileText className="mr-2 h-4 w-4" />
                        Generar PDF
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
