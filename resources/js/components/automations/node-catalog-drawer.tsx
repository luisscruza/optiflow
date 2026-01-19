import * as LucideIcons from 'lucide-react';
import { ChevronRight, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useNodeTypeRegistry } from './automation-context';
import type { NodeTypeDefinition } from './registry';

interface NodeCatalogDrawerProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelectNodeType: (nodeTypeKey: string) => void;
}

/**
 * Get icon component from Lucide by name.
 */
function getIconComponent(iconName: string): React.ComponentType<{ className?: string }> {
    const Icon = (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[iconName];
    return Icon ?? LucideIcons.Box;
}

const colorClasses: Record<string, { bg: string; border: string; text: string; hoverBg: string }> = {
    amber: {
        bg: 'bg-amber-50 dark:bg-amber-950/50',
        border: 'border-amber-200 dark:border-amber-800',
        text: 'text-amber-600 dark:text-amber-400',
        hoverBg: 'hover:bg-amber-100 dark:hover:bg-amber-900/50',
    },
    blue: {
        bg: 'bg-blue-50 dark:bg-blue-950/50',
        border: 'border-blue-200 dark:border-blue-800',
        text: 'text-blue-600 dark:text-blue-400',
        hoverBg: 'hover:bg-blue-100 dark:hover:bg-blue-900/50',
    },
    sky: {
        bg: 'bg-sky-50 dark:bg-sky-950/50',
        border: 'border-sky-200 dark:border-sky-800',
        text: 'text-sky-600 dark:text-sky-400',
        hoverBg: 'hover:bg-sky-100 dark:hover:bg-sky-900/50',
    },
    green: {
        bg: 'bg-green-50 dark:bg-green-950/50',
        border: 'border-green-200 dark:border-green-800',
        text: 'text-green-600 dark:text-green-400',
        hoverBg: 'hover:bg-green-100 dark:hover:bg-green-900/50',
    },
    purple: {
        bg: 'bg-purple-50 dark:bg-purple-950/50',
        border: 'border-purple-200 dark:border-purple-800',
        text: 'text-purple-600 dark:text-purple-400',
        hoverBg: 'hover:bg-purple-100 dark:hover:bg-purple-900/50',
    },
};

const categoryLabels: Record<string, string> = {
    trigger: 'Disparadores',
    action: 'Acciones',
    condition: 'Condiciones',
};

function NodeTypeCard({ definition, onClick }: { definition: NodeTypeDefinition; onClick: () => void }) {
    const Icon = getIconComponent(definition.icon);
    const colors = colorClasses[definition.color] ?? colorClasses.blue;

    return (
        <button
            onClick={onClick}
            className={`group flex w-full items-center gap-3 rounded-lg border p-3 text-left transition-all ${colors.bg} ${colors.border} ${colors.hoverBg}`}
        >
            <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${colors.bg} ${colors.text}`}>
                <Icon className="h-5 w-5" />
            </div>
            <div className="min-w-0 flex-1">
                <p className="font-medium text-foreground">{definition.label}</p>
                <p className="truncate text-xs text-muted-foreground">{definition.description}</p>
            </div>
            <ChevronRight className="h-4 w-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100" />
        </button>
    );
}

export function NodeCatalogDrawer({ open, onOpenChange, onSelectNodeType }: NodeCatalogDrawerProps) {
    const nodeTypeRegistry = useNodeTypeRegistry();
    const [search, setSearch] = useState('');

    const filteredItems = useMemo(() => {
        const allItems = [...nodeTypeRegistry.actions, ...nodeTypeRegistry.conditions];
        const showableItems = allItems.filter((item) => item.showInPalette);

        if (!search.trim()) {
            return showableItems;
        }

        const searchLower = search.toLowerCase();
        return showableItems.filter((item) => item.label.toLowerCase().includes(searchLower) || item.description.toLowerCase().includes(searchLower));
    }, [nodeTypeRegistry, search]);

    const groupedItems = useMemo(() => {
        const groups: Record<string, NodeTypeDefinition[]> = {
            action: [],
            condition: [],
        };

        for (const item of filteredItems) {
            if (groups[item.category]) {
                groups[item.category].push(item);
            }
        }

        return groups;
    }, [filteredItems]);

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="w-[400px] sm:w-[450px] px-4">
                <SheetHeader className="pb-4">
                    <SheetTitle>Catálogo de nodos</SheetTitle>
                    <SheetDescription>Selecciona un nodo para añadirlo a tu automatización</SheetDescription>
                </SheetHeader>

                {/* Search */}
                <div className="relative mb-4">
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input placeholder="Buscar nodos..." value={search} onChange={(e) => setSearch(e.target.value)} className="pr-9 pl-9" />
                    {search && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="absolute top-1/2 right-1 h-7 w-7 -translate-y-1/2"
                            onClick={() => setSearch('')}
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                </div>

                <div className="h-[calc(100vh-220px)] overflow-y-auto">
                    <div className="space-y-6 pr-4">
                        {Object.entries(groupedItems).map(([category, items]) => {
                            if (items.length === 0) return null;

                            return (
                                <div key={category}>
                                    <h3 className="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                        {categoryLabels[category] ?? category}
                                    </h3>
                                    <div className="space-y-2">
                                        {items.map((item) => (
                                            <NodeTypeCard key={item.key} definition={item} onClick={() => onSelectNodeType(item.key)} />
                                        ))}
                                    </div>
                                </div>
                            );
                        })}

                        {filteredItems.length === 0 && (
                            <div className="py-8 text-center text-muted-foreground">
                                <p>No se encontraron nodos</p>
                                <p className="text-sm">Intenta con otra búsqueda</p>
                            </div>
                        )}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
