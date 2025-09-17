import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Plus, TrendingDown, TrendingUp, Coins, ArrowUpDown } from 'lucide-react';
import { useState } from 'react';
import CreateCurrencyModal from '@/components/currencies/CreateCurrencyModal';
import CreateRateModal from '@/components/currencies/CreateRateModal';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Monedas',
        href: '/currencies',
    },
];

interface Currency {
    id: number;
    code: string;
    name: string;
    symbol: string;
    is_default: boolean;
    current_rate: number;
    rate_variation: number;
}

interface CurrencyRate {
    id: number;
    currency_id: number;
    rate: number;
    date: string;
}

interface Props {
    currencies: Currency[];
    historicalRates: CurrencyRate[];
    defaultCurrency: Currency;
}

export default function CurrenciesIndex({ currencies, historicalRates, defaultCurrency }: Props) {
    const [selectedCurrency, setSelectedCurrency] = useState<Currency | null>(
        currencies.find(c => !c.is_default) || null
    );
    const [isCreateCurrencyModalOpen, setIsCreateCurrencyModalOpen] = useState(false);
    const [isCreateRateModalOpen, setIsCreateRateModalOpen] = useState(false);
    const [defaultAmount, setDefaultAmount] = useState<string>('');
    const [convertedAmount, setConvertedAmount] = useState<string>('');
    const [conversionDirection, setConversionDirection] = useState<'to-foreign' | 'to-default'>('to-foreign');

    // Get recent rates for the selected currency (last 7 days)
    const getRecentRates = () => {
        if (!selectedCurrency) return [];
        
        return historicalRates
            .filter(rate => rate.currency_id === selectedCurrency.id)
            .slice(-7)
            .reverse();
    };

    // Currency conversion functions
    const convertCurrency = (amount: number, fromDefault: boolean): number => {
        if (!selectedCurrency || amount === 0) return 0;
        
        if (fromDefault) {
            // Convert from default currency to selected currency
            return amount * selectedCurrency.current_rate;
        } else {
            // Convert from selected currency to default currency
            return amount / selectedCurrency.current_rate;
        }
    };

    const handleDefaultAmountChange = (value: string) => {
        setDefaultAmount(value);
        const numValue = parseFloat(value) || 0;
        
        if (conversionDirection === 'to-foreign') {
            const converted = convertCurrency(numValue, true);
            setConvertedAmount(converted > 0 ? converted.toFixed(2) : '');
        } else {
            const converted = convertCurrency(numValue, false);
            setConvertedAmount(converted > 0 ? converted.toFixed(2) : '');
        }
    };

    const handleConvertedAmountChange = (value: string) => {
        setConvertedAmount(value);
        const numValue = parseFloat(value) || 0;
        
        if (conversionDirection === 'to-foreign') {
            const converted = convertCurrency(numValue, false);
            setDefaultAmount(converted > 0 ? converted.toFixed(2) : '');
        } else {
            const converted = convertCurrency(numValue, true);
            setDefaultAmount(converted > 0 ? converted.toFixed(2) : '');
        }
    };

    const swapConversion = () => {
        setConversionDirection(prev => prev === 'to-foreign' ? 'to-default' : 'to-foreign');
        // Swap the values
        const tempDefault = defaultAmount;
        const tempConverted = convertedAmount;
        setDefaultAmount(tempConverted);
        setConvertedAmount(tempDefault);
    };

    // Reset converter when currency changes
    const handleCurrencySelect = (currency: Currency) => {
        setSelectedCurrency(currency);
        setDefaultAmount('');
        setConvertedAmount('');
        setConversionDirection('to-foreign');
    };

    const formatCurrency = (amount: number, currency: Currency) => {
        return new Intl.NumberFormat('es-DO', {
            style: 'currency',
            currency: currency.code,
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-DO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    return (
        <>
            <Head title="Monedas" />
            <AppLayout breadcrumbs={breadcrumbs}>
                <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Monedas</h1>
                        <p className="text-muted-foreground">
                            Gestiona las monedas y tasas de cambio de tu empresa
                        </p>
                    </div>
                    <Button 
                        variant="outline"
                        onClick={() => setIsCreateCurrencyModalOpen(true)}
                    >
                        <Coins className="mr-2 h-4 w-4" />
                        Nueva Moneda
                    </Button>
                </div>

                {/* Currency Overview Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {currencies.map((currency) => (
                        <Card 
                            key={currency.id} 
                            className={`cursor-pointer transition-colors ${
                                selectedCurrency?.id === currency.id ? 'ring-2 ring-primary' : ''
                            } ${currency.is_default ? 'border-green-200 bg-green-50' : ''}`}
                            onClick={() => !currency.is_default && handleCurrencySelect(currency)}
                        >
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-lg">
                                        {currency.code}
                                        {currency.is_default && (
                                            <Badge variant="default" className="ml-2 bg-green-600">
                                                Predeterminada
                                            </Badge>
                                        )}
                                    </CardTitle>
                                    <div className="text-2xl">{currency.symbol}</div>
                                </div>
                                <CardDescription>{currency.name}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {!currency.is_default && (
                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Tasa actual</span>
                                            <span className="font-semibold">
                                                {currency.current_rate.toFixed(4)}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm text-muted-foreground">Variación</span>
                                            <div className={`flex items-center ${
                                                currency.rate_variation > 0 
                                                    ? 'text-green-600' 
                                                    : currency.rate_variation < 0 
                                                    ? 'text-red-600' 
                                                    : 'text-gray-600'
                                            }`}>
                                                {currency.rate_variation > 0 ? (
                                                    <TrendingUp className="h-3 w-3 mr-1" />
                                                ) : currency.rate_variation < 0 ? (
                                                    <TrendingDown className="h-3 w-3 mr-1" />
                                                ) : null}
                                                <span className="text-sm font-medium">
                                                    {currency.rate_variation > 0 ? '+' : ''}
                                                    {currency.rate_variation.toFixed(2)}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                {currency.is_default && (
                                    <p className="text-sm text-muted-foreground">
                                        Esta es tu moneda base para todas las conversiones
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Currency Converter */}
                <Card>
                    <CardHeader>
                        <CardTitle>Conversor de Monedas</CardTitle>
                        <CardDescription>
                            Convierte entre {defaultCurrency.code} y otras monedas
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {selectedCurrency ? (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="amount">
                                            {conversionDirection === 'to-foreign' 
                                                ? `${defaultCurrency.code} (${defaultCurrency.symbol})`
                                                : `${selectedCurrency.code} (${selectedCurrency.symbol})`
                                            }
                                        </Label>
                                        <Input
                                            id="amount"
                                            type="number"
                                            placeholder="0.00"
                                            step="0.01"
                                            value={defaultAmount}
                                            onChange={(e) => handleDefaultAmountChange(e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="converted">
                                            {conversionDirection === 'to-foreign' 
                                                ? `${selectedCurrency.code} (${selectedCurrency.symbol})`
                                                : `${defaultCurrency.code} (${defaultCurrency.symbol})`
                                            }
                                        </Label>
                                        <Input
                                            id="converted"
                                            type="number"
                                            placeholder="0.00"
                                            step="0.01"
                                            value={convertedAmount}
                                            onChange={(e) => handleConvertedAmountChange(e.target.value)}
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center justify-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={swapConversion}
                                        className="flex items-center gap-2"
                                    >
                                        <ArrowUpDown className="h-4 w-4" />
                                        Intercambiar
                                    </Button>
                                </div>
                                {defaultAmount && convertedAmount && (
                                    <div className="bg-muted p-3 rounded-lg">
                                        <p className="text-sm text-muted-foreground text-center">
                                            {conversionDirection === 'to-foreign' ? (
                                                <>
                                                    1 {defaultCurrency.code} = {selectedCurrency.current_rate.toFixed(4)} {selectedCurrency.code}
                                                </>
                                            ) : (
                                                <>
                                                    1 {selectedCurrency.code} = {(1 / selectedCurrency.current_rate).toFixed(4)} {defaultCurrency.code}
                                                </>
                                            )}
                                        </p>
                                    </div>
                                )}
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground text-center py-8">
                                Selecciona una moneda arriba para usar el conversor
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Historical Rates Table */}
                {selectedCurrency && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>
                                        Historial de Tasas - {selectedCurrency.code}
                                    </CardTitle>
                                    <CardDescription>
                                        Últimas tasas de cambio registradas
                                    </CardDescription>
                                </div>
                                <Button 
                                    size="sm"
                                    onClick={() => setIsCreateRateModalOpen(true)}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Agregar Tasa
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left py-2">Fecha</th>
                                            <th className="text-right py-2">
                                                Tasa ({defaultCurrency.code} → {selectedCurrency.code})
                                            </th>
                                            <th className="text-right py-2">
                                                Equivalencia
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {getRecentRates().length === 0 ? (
                                            <tr>
                                                <td colSpan={3} className="text-center py-8 text-muted-foreground">
                                                    No hay datos históricos disponibles
                                                </td>
                                            </tr>
                                        ) : (
                                            getRecentRates().map((rate, index) => (
                                                <tr key={rate.id} className="border-b">
                                                    <td className="py-2">{formatDate(rate.date)}</td>
                                                    <td className="text-right py-2 font-mono">
                                                        {rate.rate.toFixed(4)}
                                                    </td>
                                                    <td className="text-right py-2 text-muted-foreground">
                                                        1 {defaultCurrency.code} = {rate.rate.toFixed(4)} {selectedCurrency.code}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
                    </div>
                </div>

                {/* Modals */}
                <CreateCurrencyModal
                    isOpen={isCreateCurrencyModalOpen}
                    onClose={() => setIsCreateCurrencyModalOpen(false)}
                />
                
                <CreateRateModal
                    isOpen={isCreateRateModalOpen}
                    onClose={() => setIsCreateRateModalOpen(false)}
                    currencies={currencies.filter(c => !c.is_default)}
                />
            </AppLayout>
        </>
    );
}