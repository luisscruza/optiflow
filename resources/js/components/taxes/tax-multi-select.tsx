'use client';

import { Check, ChevronDown, X } from 'lucide-react';
import * as React from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { type Tax, type TaxesGroupedByType } from '@/types';

/**
 * Represents a selected tax with calculated amount
 */
export interface SelectedTax {
    id: number;
    name: string;
    type: string;
    rate: number;
    amount: number;
}

interface TaxMultiSelectProps {
    /** Taxes grouped by type from the backend */
    taxesGroupedByType: TaxesGroupedByType;
    /** Currently selected taxes */
    selectedTaxes: SelectedTax[];
    /** Callback when taxes selection changes */
    onSelectionChange: (taxes: SelectedTax[]) => void;
    /** The subtotal (after discount) to calculate tax amounts */
    taxableAmount: number;
    /** Whether the component is disabled */
    disabled?: boolean;
    /** Additional class names */
    className?: string;
    /** Placeholder text */
    placeholder?: string;
}

/**
 * Multi-select component for taxes that groups by tax type and enforces
 * exclusive vs accumulative tax selection rules.
 *
 * - Exclusive taxes (ITBIS, ISC, Exento, NoFacturable): Only one can be selected at a time
 * - Accumulative taxes (Propina Legal, Other): Can be added alongside any tax
 */
export function TaxMultiSelect({
    taxesGroupedByType,
    selectedTaxes,
    onSelectionChange,
    taxableAmount,
    disabled = false,
    className,
    placeholder = 'Seleccionar impuestos...',
}: TaxMultiSelectProps) {
    const [open, setOpen] = React.useState(false);

    // Get currently selected exclusive tax (if any)
    const selectedExclusiveTax = React.useMemo(() => {
        for (const [type, group] of Object.entries(taxesGroupedByType)) {
            if (group.isExclusive) {
                const found = selectedTaxes.find((st) => group.taxes.some((t) => t.id === st.id));
                if (found) return found;
            }
        }
        return null;
    }, [selectedTaxes, taxesGroupedByType]);

    // Calculate tax amount based on rate and taxable amount
    const calculateTaxAmount = (rate: number): number => {
        return taxableAmount * (rate / 100);
    };

    // Check if a tax is selected
    const isSelected = (taxId: number): boolean => {
        return selectedTaxes.some((st) => st.id === taxId);
    };

    // Check if a tax should be disabled
    const isTaxDisabled = (tax: Tax, group: { isExclusive: boolean }): boolean => {
        // If there's a selected exclusive tax and this is a different exclusive tax, disable it
        if (group.isExclusive && selectedExclusiveTax && selectedExclusiveTax.id !== tax.id) {
            // Check if the selected exclusive tax is from a different type
            const selectedExclusiveType = Object.entries(taxesGroupedByType).find(
                ([_, g]) => g.isExclusive && g.taxes.some((t) => t.id === selectedExclusiveTax.id),
            );
            if (selectedExclusiveType) {
                return true;
            }
        }
        return false;
    };

    // Handle tax toggle
    const handleToggleTax = (tax: Tax, group: { isExclusive: boolean; label: string }) => {
        const taxId = tax.id;

        if (isSelected(taxId)) {
            // Remove the tax
            onSelectionChange(selectedTaxes.filter((st) => st.id !== taxId));
        } else {
            // Add the tax
            const newTax: SelectedTax = {
                id: tax.id,
                name: tax.name,
                type: tax.type,
                rate: tax.rate,
                amount: calculateTaxAmount(tax.rate),
            };

            if (group.isExclusive) {
                // Replace any existing exclusive tax
                const filteredTaxes = selectedTaxes.filter((st) => {
                    for (const [_, g] of Object.entries(taxesGroupedByType)) {
                        if (g.isExclusive && g.taxes.some((t) => t.id === st.id)) {
                            return false;
                        }
                    }
                    return true;
                });
                onSelectionChange([...filteredTaxes, newTax]);
            } else {
                // Just add the accumulative tax
                onSelectionChange([...selectedTaxes, newTax]);
            }
        }
    };

    // Remove a specific tax
    const handleRemoveTax = (taxId: number, e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectionChange(selectedTaxes.filter((st) => st.id !== taxId));
    };

    // Calculate total tax rate and amount (ensure numeric values)
    const totalTaxRate = selectedTaxes.reduce((sum, t) => sum + (Number(t.rate) || 0), 0);
    const totalTaxAmount = selectedTaxes.reduce((sum, t) => sum + (Number(t.amount) || 0), 0);

    return (
        <div className={cn('relative', className)}>
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        disabled={disabled}
                        className={cn('h-auto min-h-9 w-full justify-between px-3 py-1.5', selectedTaxes.length > 0 && 'h-auto')}
                    >
                        <div className="flex flex-1 flex-wrap gap-1">
                            {selectedTaxes.length > 0 ? (
                                selectedTaxes.map((tax) => (
                                    <Badge key={tax.id} variant="secondary" className="mr-1 gap-1 text-xs">
                                        {tax.name} ({tax.rate}%)
                                        <button
                                            type="button"
                                            className="ml-0.5 rounded-full ring-offset-background outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            onClick={(e) => handleRemoveTax(tax.id, e)}
                                        >
                                            <X className="h-3 w-3 text-muted-foreground hover:text-foreground" />
                                        </button>
                                    </Badge>
                                ))
                            ) : (
                                <span className="text-muted-foreground">{placeholder}</span>
                            )}
                        </div>
                        <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-full p-0" style={{ width: 'var(--radix-popover-trigger-width)' }} align="start">
                    <Command>
                        <CommandInput placeholder="Buscar impuesto..." />
                        <CommandList>
                            <CommandEmpty>No se encontró ningún impuesto.</CommandEmpty>
                            {Object.entries(taxesGroupedByType).map(([type, group]) => (
                                <CommandGroup key={type} heading={group.label}>
                                    {group.taxes.map((tax) => {
                                        const selected = isSelected(tax.id);
                                        const taxDisabled = isTaxDisabled(tax, group);

                                        return (
                                            <CommandItem
                                                key={tax.id}
                                                value={`${tax.name}-${tax.id}`}
                                                disabled={taxDisabled}
                                                onSelect={() => handleToggleTax(tax, group)}
                                                className={cn(taxDisabled && 'cursor-not-allowed opacity-50')}
                                            >
                                                <div
                                                    className={cn(
                                                        'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                                                        selected ? 'bg-primary text-primary-foreground' : 'opacity-50 [&_svg]:invisible',
                                                    )}
                                                >
                                                    <Check className="h-4 w-4" />
                                                </div>
                                                <span className="flex-1">{tax.name}</span>
                                                <span className="ml-2 text-xs text-muted-foreground">{tax.rate}%</span>
                                            </CommandItem>
                                        );
                                    })}
                                </CommandGroup>
                            ))}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {/* Summary footer when taxes are selected */}
            {selectedTaxes.length > 0 && (
                <div className="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                    <span>Total: {totalTaxRate.toFixed(2)}%</span>
                </div>
            )}
        </div>
    );
}
