import { createContext, useContext, type ReactNode } from 'react';
import type { NodeTypeRegistry } from './registry';

interface AutomationContextValue {
    nodeTypeRegistry: NodeTypeRegistry;
}

const AutomationContext = createContext<AutomationContextValue | null>(null);

interface AutomationProviderProps {
    children: ReactNode;
    nodeTypeRegistry: NodeTypeRegistry;
}

export function AutomationProvider({ children, nodeTypeRegistry }: AutomationProviderProps) {
    return <AutomationContext.Provider value={{ nodeTypeRegistry }}>{children}</AutomationContext.Provider>;
}

export function useAutomationContext(): AutomationContextValue {
    const context = useContext(AutomationContext);
    if (!context) {
        throw new Error('useAutomationContext must be used within an AutomationProvider');
    }
    return context;
}

export function useNodeTypeRegistry(): NodeTypeRegistry {
    return useAutomationContext().nodeTypeRegistry;
}
