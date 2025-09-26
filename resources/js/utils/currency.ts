import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

/**
 * Format a value using the default currency symbol and format
 */
export function formatCurrency(amount: number | string): string {
    const { props } = usePage<SharedData>();
    const defaultCurrency = props.defaultCurrency;

    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;

    if (isNaN(numAmount)) {
        return defaultCurrency?.symbol ? `${defaultCurrency.symbol}0.00` : '$0.00';
    }

    if (!defaultCurrency) {
        return `$${new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(numAmount)}`;
    }

    return `${defaultCurrency.symbol}${new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numAmount)}`;
}

/**
 * Get the default currency symbol
 */
export function getCurrencySymbol(): string {
    const { props } = usePage<SharedData>();
    const defaultCurrency = props.defaultCurrency;

    return defaultCurrency?.symbol ?? '$';
}

/**
 * Hook to get currency formatting functions
 */
export function useCurrency() {
    const { props } = usePage<SharedData>();
    const defaultCurrency = props.defaultCurrency;

    const format = (amount: number | string): string => {
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;

        if (isNaN(numAmount)) {
            return defaultCurrency?.symbol ? `${defaultCurrency.symbol}0.00` : '$0.00';
        }

        if (!defaultCurrency) {
            return `$${new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(numAmount)}`;
        }

        return `${defaultCurrency.symbol}${new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(numAmount)}`;
    };

    const symbol = defaultCurrency?.symbol ?? '$';

    return {
        format,
        symbol,
        currency: defaultCurrency,
    };
}
