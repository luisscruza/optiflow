'use client';

import * as React from 'react';
import {
    endOfMonth,
    endOfQuarter,
    endOfWeek,
    endOfYear,
    format,
    startOfMonth,
    startOfQuarter,
    startOfWeek,
    startOfYear,
    subDays,
    subMonths,
    subQuarters,
    subYears,
} from 'date-fns';
import { es } from 'date-fns/locale';
import { Calendar as CalendarIcon, ChevronDown } from 'lucide-react';
import { DateRange } from 'react-day-picker';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

interface DateRangePickerProps {
    value?: DateRange;
    onChange?: (range: DateRange | undefined) => void;
    className?: string;
    placeholder?: string;
}

interface DatePreset {
    label: string;
    getValue: () => DateRange;
}

const getDatePresets = (): DatePreset[] => {
    const today = new Date();

    return [
        {
            label: 'Hoy',
            getValue: () => ({ from: today, to: today }),
        },
        {
            label: 'Ayer',
            getValue: () => {
                const yesterday = subDays(today, 1);
                return { from: yesterday, to: yesterday };
            },
        },
        {
            label: 'Esta semana',
            getValue: () => ({
                from: startOfWeek(today, { weekStartsOn: 1 }),
                to: endOfWeek(today, { weekStartsOn: 1 }),
            }),
        },
        {
            label: 'Semana anterior',
            getValue: () => {
                const lastWeek = subDays(today, 7);
                return {
                    from: startOfWeek(lastWeek, { weekStartsOn: 1 }),
                    to: endOfWeek(lastWeek, { weekStartsOn: 1 }),
                };
            },
        },
        {
            label: 'Este mes',
            getValue: () => ({
                from: startOfMonth(today),
                to: endOfMonth(today),
            }),
        },
        {
            label: 'Mes anterior',
            getValue: () => {
                const lastMonth = subMonths(today, 1);
                return {
                    from: startOfMonth(lastMonth),
                    to: endOfMonth(lastMonth),
                };
            },
        },
        {
            label: 'Este trimestre',
            getValue: () => ({
                from: startOfQuarter(today),
                to: endOfQuarter(today),
            }),
        },
        {
            label: 'Trimestre anterior',
            getValue: () => {
                const lastQuarter = subQuarters(today, 1);
                return {
                    from: startOfQuarter(lastQuarter),
                    to: endOfQuarter(lastQuarter),
                };
            },
        },
        {
            label: 'Este año',
            getValue: () => ({
                from: startOfYear(today),
                to: endOfYear(today),
            }),
        },
        {
            label: 'Año anterior',
            getValue: () => {
                const lastYear = subYears(today, 1);
                return {
                    from: startOfYear(lastYear),
                    to: endOfYear(lastYear),
                };
            },
        },
    ];
};

export function DateRangePicker({ value, onChange, className, placeholder = 'Seleccionar fechas' }: DateRangePickerProps) {
    const [open, setOpen] = React.useState(false);
    const [internalRange, setInternalRange] = React.useState<DateRange | undefined>(value);
    const [month, setMonth] = React.useState<Date>(value?.from || new Date());

    const presets = React.useMemo(() => getDatePresets(), []);

    // Sync internal state with external value
    React.useEffect(() => {
        setInternalRange(value);
        if (value?.from) {
            setMonth(value.from);
        }
    }, [value]);

    const handlePresetClick = (preset: DatePreset) => {
        const range = preset.getValue();
        setInternalRange(range);
        setMonth(range.from || new Date());
    };

    const handleCancel = () => {
        setInternalRange(value);
        setOpen(false);
    };

    const handleApply = () => {
        onChange?.(internalRange);
        setOpen(false);
    };

    const formatDateRange = () => {
        if (!value?.from) return placeholder;

        if (value.to) {
            return `${format(value.from, 'd MMM yyyy', { locale: es })} - ${format(value.to, 'd MMM yyyy', { locale: es })}`;
        }

        return format(value.from, 'd MMM yyyy', { locale: es });
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    className={cn('justify-start gap-2 text-left font-normal', !value && 'text-muted-foreground', className)}
                >
                    <CalendarIcon className="h-4 w-4" />
                    {formatDateRange()}
                    <ChevronDown className="ml-auto h-4 w-4 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <div className="flex">
                    {/* Presets sidebar */}
                    <div className="border-r p-2">
                        <div className="flex flex-col gap-1">
                            {presets.map((preset) => (
                                <Button
                                    key={preset.label}
                                    variant="ghost"
                                    size="sm"
                                    className="justify-start font-normal"
                                    onClick={() => handlePresetClick(preset)}
                                >
                                    {preset.label}
                                </Button>
                            ))}
                        </div>
                    </div>

                    {/* Dual calendar */}
                    <div className="p-3">
                        <Calendar
                            mode="range"
                            selected={internalRange}
                            onSelect={setInternalRange}
                            numberOfMonths={2}
                            month={month}
                            onMonthChange={setMonth}
                            locale={es}
                            weekStartsOn={1}
                            captionLayout="dropdown"
                        />
                    </div>
                </div>

                {/* Footer with actions */}
                <div className="flex items-center justify-end gap-2 border-t p-3">
                    <Button variant="ghost" onClick={handleCancel}>
                        Cancelar
                    </Button>
                    <Button onClick={handleApply}>Aplicar</Button>
                </div>
            </PopoverContent>
        </Popover>
    );
}

