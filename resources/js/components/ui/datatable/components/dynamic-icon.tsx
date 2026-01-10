import * as LucideIcons from 'lucide-react';
import * as React from 'react';

interface DynamicIconProps {
    name: string;
    className?: string;
}

export const DynamicIcon: React.FC<DynamicIconProps> = ({ name, className }) => {
    // Convert kebab-case to PascalCase (e.g., 'refresh-cw' -> 'RefreshCw')
    const iconName = name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');

    const IconComponent = (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[iconName];

    if (!IconComponent) {
        console.warn(`Icon "${name}" (${iconName}) not found in lucide-react`);
        return null;
    }

    return <IconComponent className={className} />;
};
