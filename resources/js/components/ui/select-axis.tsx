import { SearchableSelect, SearchableSelectOption } from "./searchable-select";

interface SelectAxisProps {
    value?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    triggerClassName?: string;
}

// Generate Axis options from -20.00 to +19.75 in 0.25 increments
function generateAxisOptions(): SearchableSelectOption[] {
    const options: SearchableSelectOption[] = [];

    options.push({ value: "NA", label: "N/A" });
    
    // Generate values from 0 to 180
    for (let i = 0; i <= 180; i++) {
        options.push({
            value: i.toString(),
            label: i.toString() + "°"
        });
    }
    
    return options;
}

export default function SelectAxis({
    value,
    onValueChange,
    placeholder = "Eje",
    disabled = false,
    className,
}: SelectAxisProps) {
    const AxisOptions = generateAxisOptions();
    
    return (
        <SearchableSelect
            options={AxisOptions}
            value={value}
            onValueChange={onValueChange}
            placeholder={placeholder}
            searchPlaceholder="Buscar eje..."
            emptyText="No se encontró el valor"
            disabled={disabled}
            className={className}
            triggerClassName='w-24 h-6'
        />
    );
}