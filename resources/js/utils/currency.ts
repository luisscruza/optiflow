import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

/**
 * Format a value using the default currency symbol and format
 */
export function formatCurrency(amount: number | string): string {
    const { props } = usePage<SharedData>();
    const defaultCurrency = props.defaultCurrency;
    
    // Convert to number if it's a string
    const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
    
    // Handle invalid numbers
    if (isNaN(numAmount)) {
        return defaultCurrency?.symbol ? `${defaultCurrency.symbol}0.00` : '$0.00';
    }
    
    if (!defaultCurrency) {
        // Fallback to basic formatting if no default currency is available
        return `$${numAmount.toFixed(2)}`;
    }
    
    return `${defaultCurrency.symbol}${numAmount.toFixed(2)}`;
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
        // Convert to number if it's a string
        const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount;
        
        // Handle invalid numbers
        if (isNaN(numAmount)) {
            return defaultCurrency?.symbol ? `${defaultCurrency.symbol}0.00` : '$0.00';
        }
        
        if (!defaultCurrency) {
            return `$${numAmount.toFixed(2)}`;
        }
        return `${defaultCurrency.symbol}${numAmount.toFixed(2)}`;
    };
    
    const symbol = defaultCurrency?.symbol ?? '$';
    
    return {
        format,
        symbol,
        currency: defaultCurrency,
    };
}