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
import currenciesData from '@/data/currencies.json';

interface Currency {
    code: string;
    name: string;
    symbol: string;
    flag: string;
}

interface CreateCurrencyModalProps {
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateCurrencyModal({ isOpen, onClose }: CreateCurrencyModalProps) {
    const [selectedCode, setSelectedCode] = useState<string>('');
    const [initialRate, setInitialRate] = useState<string>('');
    const [isLoading, setIsLoading] = useState(false);
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    const currencies: Currency[] = currenciesData;

    const selectedCurrency = currencies.find(currency => currency.code === selectedCode);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!selectedCode || !initialRate) {
            setErrors({
                ...(selectedCode ? {} : { code: 'Por favor selecciona una moneda' }),
                ...(initialRate ? {} : { initial_rate: 'Por favor ingresa el tipo de cambio' }),
            });
            return;
        }

        const rate = parseFloat(initialRate);
        if (isNaN(rate) || rate <= 0) {
            setErrors({ initial_rate: 'El tipo de cambio debe ser un número válido mayor a 0' });
            return;
        }

        setIsLoading(true);
        setErrors({});

        try {
            router.post('/currencies', {
                code: selectedCode,
                name: selectedCurrency?.name || '',
                symbol: selectedCurrency?.symbol || '',
                initial_rate: rate,
            }, {
                onSuccess: () => {
                    onClose();
                    setSelectedCode('');
                    setInitialRate('');
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
            console.error('Error creating currency:', error);
        }
    };

    const handleClose = () => {
        if (!isLoading) {
            onClose();
            setSelectedCode('');
            setInitialRate('');
            setErrors({});
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Nueva Moneda</DialogTitle>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="currency-select">Moneda</Label>
                        <Select value={selectedCode} onValueChange={setSelectedCode}>
                            <SelectTrigger id="currency-select">
                                <SelectValue placeholder="Selecciona una moneda" />
                            </SelectTrigger>
                            <SelectContent className="max-h-60">
                                {currencies.map((currency) => (
                                    <SelectItem key={currency.code} value={currency.code}>
                                        <div className="flex items-center gap-2">
                                            <span className="text-lg">{currency.flag}</span>
                                            <span className="font-medium">{currency.code}</span>
                                            <span className="text-sm text-muted-foreground">
                                                {currency.symbol}
                                            </span>
                                            <span className="text-sm text-muted-foreground truncate">
                                                {currency.name}
                                            </span>
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.code && (
                            <p className="text-sm text-destructive">{errors.code}</p>
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
                            value={initialRate}
                            onChange={(e) => setInitialRate(e.target.value)}
                            disabled={isLoading}
                        />
                        {errors.initial_rate && (
                            <p className="text-sm text-destructive">{errors.initial_rate}</p>
                        )}
                        <p className="text-xs text-muted-foreground">
                            Ingresa cuántas unidades de esta moneda equivalen a 1 unidad de tu moneda base
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
                            disabled={isLoading || !selectedCode || !initialRate}
                        >
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Crear Moneda
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}