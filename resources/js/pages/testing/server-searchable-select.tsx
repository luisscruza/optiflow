import { ServerSearchableSelect, type ServerSearchableSelectOption } from '@/components/ui/server-searchable-select';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const preloadOptions: ServerSearchableSelectOption[] = [
    { value: 'alpha', label: 'Preloaded Alpha' },
    { value: 'bravo', label: 'Preloaded Bravo' },
];

export default function ServerSearchableSelectPreload() {
    const [options, setOptions] = useState<ServerSearchableSelectOption[]>([]);

    const handleSearchChange = (query: string) => {
        const normalizedQuery = query.trim().toLowerCase();

        if (normalizedQuery.length === 0) {
            setOptions(preloadOptions);
            return;
        }

        setOptions(preloadOptions.filter((option) => option.label.toLowerCase().includes(normalizedQuery)));
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-8">
            <Head title="Server Searchable Select Preload" />
            <div className="w-full max-w-sm space-y-4">
                <h1 className="text-lg font-semibold text-foreground">Server Searchable Select Preload</h1>
                <ServerSearchableSelect
                    preload
                    options={options}
                    onSearchChange={handleSearchChange}
                    placeholder="Select preload option"
                    searchPlaceholder="Search options"
                />
            </div>
        </div>
    );
}
