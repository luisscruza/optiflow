/**
 * Output schema field definition.
 */
export interface OutputSchemaField {
    type: 'string' | 'number' | 'boolean' | 'object' | 'array' | 'mixed';
    description: string;
}

/**
 * Node type definition from the backend registry.
 */
export interface NodeTypeDefinition {
    key: string;
    category: 'trigger' | 'action' | 'condition';
    label: string;
    description: string;
    icon: string;
    color: string;
    reactNodeType: string;
    defaultConfig: Record<string, unknown>;
    allowMultiple: boolean;
    showInPalette: boolean;
    eventKey: string | null;
    inspectorComponent: string | null;
    outputSchema: Record<string, OutputSchemaField>;
}

/**
 * Grouped node types from backend.
 */
export interface NodeTypeRegistry {
    triggers: NodeTypeDefinition[];
    actions: NodeTypeDefinition[];
    conditions: NodeTypeDefinition[];
}

/**
 * Get a node type definition by key from the registry.
 */
export function getNodeType(registry: NodeTypeRegistry, key: string): NodeTypeDefinition | undefined {
    return [...registry.triggers, ...registry.actions, ...registry.conditions].find((def) => def.key === key);
}

/**
 * Get all node types as a flat array.
 */
export function getAllNodeTypes(registry: NodeTypeRegistry): NodeTypeDefinition[] {
    return [...registry.triggers, ...registry.actions, ...registry.conditions];
}

/**
 * Check if a node type key is a trigger.
 */
export function isTriggerType(registry: NodeTypeRegistry, key: string): boolean {
    return registry.triggers.some((def) => def.key === key);
}

/**
 * Check if a node type key is an action.
 */
export function isActionType(registry: NodeTypeRegistry, key: string): boolean {
    return registry.actions.some((def) => def.key === key);
}

/**
 * Check if a node type key is a condition.
 */
export function isConditionType(registry: NodeTypeRegistry, key: string): boolean {
    return registry.conditions.some((def) => def.key === key);
}

/**
 * Get palette items (actions and conditions that should show in the add menu).
 */
export function getPaletteItems(registry: NodeTypeRegistry): NodeTypeDefinition[] {
    return [...registry.actions, ...registry.conditions].filter((def) => def.showInPalette);
}

/**
 * Create default node data for a given node type.
 */
export function createNodeData(
    registry: NodeTypeRegistry,
    nodeTypeKey: string,
): { label: string; nodeType: string; config: Record<string, unknown> } | null {
    const definition = getNodeType(registry, nodeTypeKey);
    if (!definition) return null;

    return {
        label: definition.label,
        nodeType: definition.key,
        config: { ...definition.defaultConfig },
    };
}

/**
 * Get the output schema for a given node type.
 */
export function getOutputSchema(registry: NodeTypeRegistry, nodeTypeKey: string): Record<string, OutputSchemaField> {
    const definition = getNodeType(registry, nodeTypeKey);
    return definition?.outputSchema ?? {};
}

/**
 * Get grouped output schema fields (by parent object).
 */
export function getGroupedOutputSchema(
    registry: NodeTypeRegistry,
    nodeTypeKey: string,
): Map<string, { type: string; description: string; children: { key: string; type: string; description: string }[] }> {
    const schema = getOutputSchema(registry, nodeTypeKey);
    const groups = new Map<string, { type: string; description: string; children: { key: string; type: string; description: string }[] }>();

    for (const [key, value] of Object.entries(schema)) {
        const parts = key.split('.');
        if (parts.length === 1) {
            // Top-level field
            if (!groups.has(key)) {
                groups.set(key, { type: value.type, description: value.description, children: [] });
            }
        } else {
            // Nested field - add to parent
            const parent = parts[0];
            const childKey = parts.slice(1).join('.');

            if (!groups.has(parent)) {
                groups.set(parent, { type: 'object', description: '', children: [] });
            }
            groups.get(parent)!.children.push({ key: childKey, type: value.type, description: value.description });
        }
    }

    return groups;
}
