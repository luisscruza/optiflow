import { SearchableSelect, SearchableSelectOption } from "./searchable-select";

interface SelectAddProps {
    value?: string;
    onValueChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
    triggerClassName?: string;
}

// Generate Add options from -20.00 to +19.75 in 0.25 increments
function generateAddOptions(): SearchableSelectOption[] {
    const options: SearchableSelectOption[] = [];

    options.push({ value: "NA", label: "N/A" });
    
    // Generate values from +1.00 to +3.00 in 0.25 increments
    for (let i = 100; i <= 300; i += 25) {
        options.push({
            value: (i / 100).toFixed(2),
            label:  "+" + (i / 100).toFixed(2),
        });
    }

    return options;
}

export default function SelectAdd({
    value,
    onValueChange,
    placeholder = "Adición",
    disabled = false,
    className,
}: SelectAddProps) {
    const AddOptions = generateAddOptions();
    
    return (
        <SearchableSelect
            options={AddOptions}
            value={value}
            onValueChange={onValueChange}
            placeholder={placeholder}
            searchPlaceholder="Buscar adición..."
            emptyText="No se encontró el valor"
            disabled={disabled}
            triggerClassName='w-24 h-6'
        />
    );
}