import { SearchableSelect, SearchableSelectOption } from "./searchable-select";

interface SelectSphereProps {
    value?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    triggerClassName?: string;
}

// Generate sphere options from -20.00 to +19.75 in 0.25 increments
function generateSphereOptions(): SearchableSelectOption[] {
    const options: SearchableSelectOption[] = [];
    
    // Generate negative values from -20.00 to -0.25
    for (let i = -2000; i <= -25; i += 25) {
        const value = (i / 100).toFixed(2);
        options.push({
            value: value,
            label: value
        });
    }
    
    // Add 0.00
    options.push({ value: "0.00", label: "0.00" });
    
    // Generate positive values from +0.25 to +19.75
    for (let i = 25; i <= 1975; i += 25) {
        const value = (i / 100).toFixed(2);
        options.push({
            value: `+${value}`,
            label: `+${value}`
        });
    }
    
    return options;
}

export default function SelectSphere({
    value,
    onValueChange,
    placeholder = "Esfera",
    disabled = false,
    className,
    triggerClassName
}: SelectSphereProps) {
    const sphereOptions = generateSphereOptions();
    
    return (
        <SearchableSelect
            options={sphereOptions}
            value={value}
            onValueChange={onValueChange}
            placeholder={placeholder}
            searchPlaceholder="Buscar esfera..."
            emptyText="No se encontró el valor"
            disabled={disabled}
            className={className}
            triggerClassName='w-24 h-6'
        />
    );
}