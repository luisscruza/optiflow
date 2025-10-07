import { SearchableSelect, SearchableSelectOption } from "./searchable-select";

interface SelectCylinderProps {
    value?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    triggerClassName?: string;
}

// Generate Cylinder options from -20.00 to +19.75 in 0.25 increments
function generateCylinderOptions(): SearchableSelectOption[] {
    const options: SearchableSelectOption[] = [];

    options.push({ value: "PL", label: "Plano" });
    
    // Generate negative values from -10.00 to -0.00
    for (let i = -1000; i <= -0; i += 25) {
        const value = (i / 100).toFixed(2);
        options.push({
            value: value,
            label: value
        });
    }
    
    return options;
}

export default function SelectCylinder({
    value,
    onValueChange,
    placeholder = "Cilindro",
    disabled = false,
    className,
    triggerClassName
}: SelectCylinderProps) {
    const CylinderOptions = generateCylinderOptions();
    
    return (
        <SearchableSelect
            options={CylinderOptions}
            value={value}
            onValueChange={onValueChange}
            placeholder={placeholder}
            searchPlaceholder="Buscar cilindro..."
            emptyText="No se encontró el valor"
            disabled={disabled}
            className={className}
            triggerClassName='w-24 h-6'
        />
    );
}