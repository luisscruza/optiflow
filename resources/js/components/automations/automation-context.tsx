import { createContext, useContext, type ReactNode } from 'react';
import type { WorkflowOption } from './automation-builder';
import type { NodeTypeRegistry } from './registry';

interface AutomationContextValue {
    nodeTypeRegistry: NodeTypeRegistry;
    workflows: WorkflowOption[];
}

const AutomationContext = createContext<AutomationContextValue | null>(null);

interface AutomationProviderProps {
    children: ReactNode;
    nodeTypeRegistry: NodeTypeRegistry;
    workflows: WorkflowOption[];
}

export function AutomationProvider({ children, nodeTypeRegistry, workflows }: AutomationProviderProps) {
    return <AutomationContext.Provider value={{ nodeTypeRegistry, workflows }}>{children}</AutomationContext.Provider>;
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

export function useWorkflows(): WorkflowOption[] {
    return useAutomationContext().workflows;
}
