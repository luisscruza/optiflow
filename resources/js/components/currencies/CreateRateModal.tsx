import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Loader2 } from 'lucide-react';

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
}

interface CreateRateModalProps {
    isOpen: boolean;
    onClose: () => void;
    currencies: Currency[];
}

export default function CreateRateModal({ isOpen, onClose, currencies }: CreateRateModalProps) {
    const [selectedCurrencyId, setSelectedCurrencyId] = useState<string>('');
    const [exchangeRate, setExchangeRate] = useState<string>('');
    const [effectiveDate, setEffectiveDate] = useState<string>(new Date().toISOString().split('T')[0]);
    const [isLoading, setIsLoading] = useState(false);
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    const selectedCurrency = currencies.find(currency => currency.id.toString() === selectedCurrencyId);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!selectedCurrencyId || !exchangeRate || !effectiveDate) {
            setErrors({
                ...(selectedCurrencyId ? {} : { currency_id: 'Por favor selecciona una moneda' }),
                ...(exchangeRate ? {} : { rate: 'Por favor ingresa el tipo de cambio' }),
                ...(effectiveDate ? {} : { effective_date: 'Por favor selecciona una fecha' }),
            });
            return;
        }

        const rate = parseFloat(exchangeRate);
        if (isNaN(rate) || rate <= 0) {
            setErrors({ rate: 'El tipo de cambio debe ser un número válido mayor a 0' });
            return;
        }

        setIsLoading(true);
        setErrors({});

        try {
            router.post(`/currencies/${selectedCurrencyId}/rates`, {
                rate: rate,
                effective_date: effectiveDate,
            }, {
                onSuccess: () => {
                    onClose();
                    setSelectedCurrencyId('');
                    setExchangeRate('');
                    setEffectiveDate(new Date().toISOString().split('T')[0]);
                },
                onError: (errors) => {
                    setErrors(errors);
                },
                onFinish: () => {
                    setIsLoading(false);
                },
            });
        } catch (error) {
            setIsLoading(false);
            console.error('Error creating currency rate:', error);
        }
    };

    const handleClose = () => {
        if (!isLoading) {
            onClose();
            setSelectedCurrencyId('');
            setExchangeRate('');
            setEffectiveDate(new Date().toISOString().split('T')[0]);
            setErrors({});
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Nueva Tasa de Cambio</DialogTitle>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="currency-select">Moneda</Label>
                        <Select value={selectedCurrencyId} onValueChange={setSelectedCurrencyId}>
                            <SelectTrigger id="currency-select">
                                <SelectValue placeholder="Selecciona una moneda" />
                            </SelectTrigger>
                            <SelectContent>
                                {currencies.map((currency) => (
                                    <SelectItem key={currency.id} value={currency.id.toString()}>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{currency.code}</span>
                                            <span className="text-sm text-muted-foreground">
                                                {currency.symbol}
                                            </span>
                                            <span className="text-sm text-muted-foreground">
                                                {currency.name}
                                            </span>
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.currency_id && (
                            <p className="text-sm text-destructive">{errors.currency_id}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="exchange-rate">Tipo de Cambio</Label>
                        <Input
                            id="exchange-rate"
                            type="number"
                            step="0.0001"
                            min="0"
                            placeholder="1.0000"
                            value={exchangeRate}
                            onChange={(e) => setExchangeRate(e.target.value)}
                            disabled={isLoading}
                        />
                        {errors.rate && (
                            <p className="text-sm text-destructive">{errors.rate}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            Ingresa cuántas unidades de esta moneda equivalen a 1 unidad de tu moneda base
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="effective-date">Fecha Efectiva</Label>
                        <Input
                            id="effective-date"
                            type="date"
                            value={effectiveDate}
                            onChange={(e) => setEffectiveDate(e.target.value)}
                            disabled={isLoading}
                        />
                        {errors.effective_date && (
                            <p className="text-sm text-destructive">{errors.effective_date}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            La fecha desde la cual será efectiva esta tasa de cambio
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                            disabled={isLoading}
                        >
                            Cancelar
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={isLoading || !selectedCurrencyId || !exchangeRate || !effectiveDate}
                        >
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Crear Tasa
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}