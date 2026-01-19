import {
    Background,
    Controls,
    MarkerType,
    MiniMap,
    Panel,
    ReactFlow,
    addEdge,
    useEdgesState,
    useNodesState,
    type Connection,
    type Edge,
    type Node,
    type NodeTypes,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import * as LucideIcons from 'lucide-react';
import { Plus, Redo2, Save, Undo2 } from 'lucide-react';
import { useCallback, useMemo, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import { AutomationProvider } from './automation-context';
import { NodeCatalogDrawer } from './node-catalog-drawer';
import { NodeInspector } from './node-inspector';
import { ConditionNode } from './nodes/condition-node';
import { TelegramNode } from './nodes/telegram-node';
import { TriggerNode } from './nodes/trigger-node';
import { WebhookNode } from './nodes/webhook-node';
import { WhatsappNode } from './nodes/whatsapp-node';
import { getNodeType, type NodeTypeRegistry } from './registry';

export type AutomationNodeData = {
    label: string;
    nodeType: string;
    config: Record<string, unknown>;
};

export type AutomationNode = Node<AutomationNodeData>;
export type AutomationEdge = Edge;

type ClipboardPayload = {
    nodes: AutomationNode[];
    edges: AutomationEdge[];
};

type FlowSnapshot = {
    nodes: AutomationNode[];
    edges: AutomationEdge[];
};

function cloneSnapshot(snapshot: FlowSnapshot): FlowSnapshot {
    if (typeof structuredClone === 'function') {
        return structuredClone(snapshot) as FlowSnapshot;
    }

    return JSON.parse(JSON.stringify(snapshot)) as FlowSnapshot;
}

const nodeTypes: NodeTypes = {
    trigger: TriggerNode,
    webhook: WebhookNode,
    telegram: TelegramNode,
    whatsapp: WhatsappNode,
    condition: ConditionNode,
};

export type WorkflowField = {
    id: string;
    name: string;
    key: string;
    type: 'text' | 'textarea' | 'number' | 'date' | 'select' | 'boolean';
    is_required: boolean;
};

export type WorkflowOption = {
    id: string;
    name: string;
    invoice_requirement: 'optional' | 'required' | null;
    stages: { id: string; name: string }[];
    fields: WorkflowField[];
};

type TemplateVariable = {
    label: string;
    token: string;
    description: string;
};

type TelegramBotOption = {
    id: string;
    name: string;
    bot_username: string | null;
    default_chat_id: string | null;
};

type WhatsappAccountOption = {
    id: string;
    name: string;
    display_phone_number: string | null;
    business_account_id: string | null;
};

interface AutomationBuilderProps {
    workflows: WorkflowOption[];
    templateVariables: TemplateVariable[];
    telegramBots?: TelegramBotOption[];
    whatsappAccounts?: WhatsappAccountOption[];
    nodeTypeRegistry: NodeTypeRegistry;
    initialNodes?: AutomationNode[];
    initialEdges?: AutomationEdge[];
    onSave: (nodes: AutomationNode[], edges: AutomationEdge[], name: string, isActive: boolean) => void;
    automationName?: string;
    automationIsActive?: boolean;
    isSaving?: boolean;
}

function getDefaultTriggerNode(registry: NodeTypeRegistry): AutomationNode {
    const triggerDef = getNodeType(registry, 'workflow.stage_entered');
    return {
        id: 'trigger-1',
        type: 'trigger',
        position: { x: 100, y: 200 },
        data: {
            label: triggerDef?.label ?? 'Cambio de etapa',
            nodeType: 'workflow.stage_entered',
            config: triggerDef?.defaultConfig ?? {
                workflow_id: '',
                stage_id: '',
            },
        },
    };
}

const defaultEdges: AutomationEdge[] = [];

/**
 * Get icon component from Lucide by name.
 */
function getIconComponent(iconName: string): React.ComponentType<{ className?: string }> {
    const Icon = (LucideIcons as Record<string, React.ComponentType<{ className?: string }>>)[iconName];
    return Icon ?? LucideIcons.Plus;
}

export function AutomationBuilder({
    workflows,
    templateVariables,
    telegramBots = [],
    whatsappAccounts = [],
    nodeTypeRegistry,
    initialNodes,
    initialEdges,
    onSave,
    automationName = '',
    automationIsActive = true,
    isSaving = false,
}: AutomationBuilderProps) {
    const defaultNodes = useMemo(() => [getDefaultTriggerNode(nodeTypeRegistry)], [nodeTypeRegistry]);
    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes ?? defaultNodes);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges ?? defaultEdges);
    const [selectedNode, setSelectedNode] = useState<AutomationNode | null>(null);
    const [name, setName] = useState(automationName);
    const [isActive, setIsActive] = useState(automationIsActive);
    const [isCatalogOpen, setIsCatalogOpen] = useState(false);
    const [clipboard, setClipboard] = useState<ClipboardPayload | null>(null);
    const pasteCountRef = useRef(0);
    const canvasRef = useRef<HTMLDivElement | null>(null);

    const isRestoringRef = useRef(false);
    const historyRef = useRef<{ past: FlowSnapshot[]; future: FlowSnapshot[] }>({ past: [], future: [] });
    const recordQueuedRef = useRef(false);
    const [canUndo, setCanUndo] = useState(false);
    const [canRedo, setCanRedo] = useState(false);

    const syncHistoryState = useCallback(() => {
        setCanUndo(historyRef.current.past.length > 0);
        setCanRedo(historyRef.current.future.length > 0);
    }, []);

    const recordSnapshot = useCallback(() => {
        if (isRestoringRef.current) {
            return;
        }

        // Avoid recording multiple snapshots for a single UI gesture.
        if (recordQueuedRef.current) {
            return;
        }

        recordQueuedRef.current = true;
        queueMicrotask(() => {
            recordQueuedRef.current = false;
        });

        historyRef.current.past.push(cloneSnapshot({ nodes, edges }));
        historyRef.current.future = [];

        // Keep memory bounded.
        if (historyRef.current.past.length > 100) {
            historyRef.current.past.shift();
        }

        syncHistoryState();
    }, [nodes, edges, syncHistoryState]);

    const undo = useCallback(() => {
        const { past, future } = historyRef.current;
        if (past.length === 0) {
            return;
        }

        const previous = past.pop();
        if (!previous) {
            return;
        }

        future.push(cloneSnapshot({ nodes, edges }));

        isRestoringRef.current = true;
        setNodes(previous.nodes);
        setEdges(previous.edges);
        setSelectedNode(null);

        queueMicrotask(() => {
            isRestoringRef.current = false;
            syncHistoryState();
        });
    }, [nodes, edges, setEdges, setNodes, syncHistoryState]);

    const redo = useCallback(() => {
        const { past, future } = historyRef.current;
        if (future.length === 0) {
            return;
        }

        const next = future.pop();
        if (!next) {
            return;
        }

        past.push(cloneSnapshot({ nodes, edges }));

        isRestoringRef.current = true;
        setNodes(next.nodes);
        setEdges(next.edges);
        setSelectedNode(null);

        queueMicrotask(() => {
            isRestoringRef.current = false;
            syncHistoryState();
        });
    }, [nodes, edges, setEdges, setNodes, syncHistoryState]);

    const selectedNodeIds = useMemo(() => {
        const ids = new Set<string>();
        for (const node of nodes) {
            if (node.selected) {
                ids.add(node.id);
            }
        }
        return ids;
    }, [nodes]);

    const onConnect = useCallback(
        (params: Connection) => {
            recordSnapshot();
            setEdges((eds) =>
                addEdge(
                    {
                        ...params,
                        markerEnd: { type: MarkerType.ArrowClosed },
                        style: { strokeWidth: 2 },
                        animated: true,
                    },
                    eds,
                ),
            );
        },
        [recordSnapshot, setEdges],
    );

    const onNodeClick = useCallback((_event: React.MouseEvent, node: Node) => {
        setSelectedNode(node as AutomationNode);
    }, []);

    const onPaneClick = useCallback(() => {
        setSelectedNode(null);
    }, []);

    const shouldIgnoreKeyboardEvent = (event: React.KeyboardEvent): boolean => {
        const target = event.target as HTMLElement | null;
        if (!target) return false;

        const tagName = target.tagName?.toLowerCase();
        if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
            return true;
        }

        if (target.isContentEditable) {
            return true;
        }

        return false;
    };

    const copySelectionToClipboard = useCallback(() => {
        const nodesToCopy = nodes.filter((n) => n.selected && n.data.nodeType !== 'workflow.stage_entered');
        if (nodesToCopy.length === 0) {
            return;
        }

        const nodeIdSet = new Set(nodesToCopy.map((n) => n.id));
        const edgesToCopy = edges.filter((e) => nodeIdSet.has(e.source) && nodeIdSet.has(e.target));

        setClipboard({
            nodes: nodesToCopy,
            edges: edgesToCopy,
        });
    }, [nodes, edges]);

    const pasteClipboard = useCallback(() => {
        if (!clipboard) {
            return;
        }

        recordSnapshot();

        pasteCountRef.current += 1;
        const offset = 40 + (pasteCountRef.current - 1) * 10;

        const idMap = new Map<string, string>();
        const now = Date.now();

        const newNodes: AutomationNode[] = clipboard.nodes.map((node, idx) => {
            const newId = `${node.id}-copy-${now}-${idx}`;
            idMap.set(node.id, newId);

            return {
                ...node,
                id: newId,
                position: {
                    x: node.position.x + offset,
                    y: node.position.y + offset,
                },
                selected: true,
            };
        });

        const newEdges: AutomationEdge[] = clipboard.edges
            .map((edge, idx) => {
                const newSource = idMap.get(edge.source);
                const newTarget = idMap.get(edge.target);
                if (!newSource || !newTarget) {
                    return null;
                }

                return {
                    ...edge,
                    id: `edge-copy-${now}-${idx}`,
                    source: newSource,
                    target: newTarget,
                    selected: false,
                };
            })
            .filter((e): e is AutomationEdge => e !== null);

        setNodes((prev) => [...prev.map((n) => ({ ...n, selected: false })), ...newNodes]);
        setEdges((prev) => [...prev.map((e) => ({ ...e, selected: false })), ...newEdges]);

        setSelectedNode(newNodes[0] ?? null);
    }, [clipboard, recordSnapshot, setNodes, setEdges]);

    const onKeyDown = useCallback(
        (event: React.KeyboardEvent) => {
            if (shouldIgnoreKeyboardEvent(event)) {
                return;
            }

            const isMod = event.metaKey || event.ctrlKey;
            if (!isMod) {
                return;
            }

            const key = event.key.toLowerCase();
            if (key === 'z') {
                event.preventDefault();
                if (event.shiftKey) {
                    redo();
                } else {
                    undo();
                }
                return;
            }

            if (key === 'y') {
                event.preventDefault();
                redo();
                return;
            }

            if (key === 'c') {
                event.preventDefault();
                copySelectionToClipboard();
            }

            if (key === 'v') {
                event.preventDefault();
                pasteClipboard();
            }
        },
        [copySelectionToClipboard, pasteClipboard, redo, undo],
    );

    // Dynamic node creation based on registry
    const addNodeByType = useCallback(
        (nodeTypeKey: string) => {
            const definition = getNodeType(nodeTypeRegistry, nodeTypeKey);
            if (!definition) return;

            recordSnapshot();
            const newNode: AutomationNode = {
                id: `${definition.reactNodeType}-${Date.now()}`,
                type: definition.reactNodeType,
                position: { x: 400, y: 200 + Math.random() * 100 },
                data: {
                    label: definition.label,
                    nodeType: definition.key,
                    config: { ...definition.defaultConfig },
                },
            };
            setNodes((nds) => [...nds, newNode]);
        },
        [nodeTypeRegistry, recordSnapshot, setNodes],
    );

    const updateNodeConfig = useCallback(
        (nodeId: string, config: Record<string, unknown>) => {
            recordSnapshot();
            setNodes((nds) =>
                nds.map((node) => {
                    if (node.id === nodeId) {
                        return {
                            ...node,
                            data: {
                                ...node.data,
                                config: { ...node.data.config, ...config },
                            },
                        };
                    }
                    return node;
                }),
            );

            // Update selected node if it's the one being edited
            setSelectedNode((prev) => {
                if (prev && prev.id === nodeId) {
                    return {
                        ...prev,
                        data: {
                            ...prev.data,
                            config: { ...prev.data.config, ...config },
                        },
                    };
                }
                return prev;
            });
        },
        [recordSnapshot, setNodes],
    );

    const updateNodeType = useCallback(
        (nodeId: string, nodeType: string) => {
            recordSnapshot();
            setNodes((nds) =>
                nds.map((node) => {
                    if (node.id === nodeId) {
                        return {
                            ...node,
                            data: {
                                ...node.data,
                                nodeType,
                            },
                        };
                    }
                    return node;
                }),
            );

            setSelectedNode((prev) => {
                if (prev && prev.id === nodeId) {
                    return {
                        ...prev,
                        data: {
                            ...prev.data,
                            nodeType,
                        },
                    };
                }
                return prev;
            });
        },
        [recordSnapshot, setNodes],
    );

    const deleteNode = useCallback(
        (nodeId: string) => {
            recordSnapshot();
            setNodes((nds) => nds.filter((node) => node.id !== nodeId));
            setEdges((eds) => eds.filter((edge) => edge.source !== nodeId && edge.target !== nodeId));
            setSelectedNode(null);
        },
        [recordSnapshot, setNodes, setEdges],
    );

    const handleSave = () => {
        onSave(nodes, edges, name, isActive);
    };

    const handleNodesChange = useCallback(
        (changes: any[]) => {
            if (!isRestoringRef.current) {
                const shouldRecord = changes.some((c) => {
                    if (c.type === 'select') {
                        return false;
                    }
                    if (c.type === 'position') {
                        return !c.dragging;
                    }
                    return c.type === 'add' || c.type === 'remove' || c.type === 'replace' || c.type === 'reset';
                });

                if (shouldRecord) {
                    recordSnapshot();
                }
            }

            onNodesChange(changes);
        },
        [onNodesChange, recordSnapshot],
    );

    const handleEdgesChange = useCallback(
        (changes: any[]) => {
            if (!isRestoringRef.current) {
                const shouldRecord = changes.some((c) => {
                    if (c.type === 'select') {
                        return false;
                    }
                    return c.type === 'add' || c.type === 'remove' || c.type === 'replace' || c.type === 'reset';
                });

                if (shouldRecord) {
                    recordSnapshot();
                }
            }

            onEdgesChange(changes);
        },
        [onEdgesChange, recordSnapshot],
    );

    return (
        <AutomationProvider nodeTypeRegistry={nodeTypeRegistry} workflows={workflows}>
            <div className="flex h-[700px] w-full gap-4">
                {/* Canvas */}
                <div
                    ref={canvasRef}
                    className="relative flex-1 rounded-lg border bg-muted/30"
                    tabIndex={0}
                    onKeyDown={onKeyDown}
                    onMouseDown={() => canvasRef.current?.focus()}
                >
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        onNodesChange={handleNodesChange}
                        onEdgesChange={handleEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        nodeTypes={nodeTypes}
                        fitView
                        snapToGrid
                        snapGrid={[15, 15]}
                        defaultEdgeOptions={{
                            markerEnd: { type: MarkerType.ArrowClosed },
                            style: { strokeWidth: 2 },
                            animated: true,
                        }}
                    >
                        <Background gap={15} />
                        <Controls />
                        <MiniMap />

                        <Panel position="top-left" className="flex flex-wrap gap-2">
                            <Button size="sm" variant="default" onClick={() => setIsCatalogOpen(true)}>
                                <Plus className="mr-1 h-4 w-4" />
                                Añadir nodo
                            </Button>
                        </Panel>

                        <Panel position="top-right" className="flex items-center gap-3">
                            <Button size="sm" variant="outline" onClick={undo} disabled={!canUndo}>
                                <Undo2 className="h-4 w-4" />
                            </Button>
                            <Button size="sm" variant="outline" onClick={redo} disabled={!canRedo}>
                                <Redo2 className="h-4 w-4" />
                            </Button>
                            <div className="flex items-center gap-2 rounded-md border bg-background px-3 py-1.5">
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Nombre de la automatización"
                                    className="w-48 border-none bg-transparent text-sm outline-none"
                                />
                            </div>
                            <label className="flex cursor-pointer items-center gap-2 rounded-md border bg-background px-3 py-1.5 text-sm">
                                <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} className="h-4 w-4" />
                                Activa
                            </label>
                            <Button size="sm" onClick={handleSave} disabled={isSaving || !name.trim()}>
                                <Save className="mr-1 h-4 w-4" />
                                {isSaving ? 'Guardando...' : 'Guardar'}
                            </Button>
                        </Panel>
                    </ReactFlow>
                </div>

                {/* Inspector Panel */}
                <NodeInspector
                    node={selectedNode}
                    workflows={workflows}
                    templateVariables={templateVariables}
                    telegramBots={telegramBots}
                    whatsappAccounts={whatsappAccounts}
                    nodeTypeRegistry={nodeTypeRegistry}
                    onUpdateConfig={updateNodeConfig}
                    onUpdateNodeType={updateNodeType}
                    onDelete={deleteNode}
                />

                {/* Node Catalog Drawer */}
                <NodeCatalogDrawer
                    open={isCatalogOpen}
                    onOpenChange={setIsCatalogOpen}
                    onSelectNodeType={(nodeTypeKey) => {
                        addNodeByType(nodeTypeKey);
                        setIsCatalogOpen(false);
                    }}
                />
            </div>
        </AutomationProvider>
    );
}
