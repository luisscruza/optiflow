<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeTypes;

use InvalidArgumentException;

/**
 * Registry for automation node types.
 *
 * Node types are registered in the AutomationServiceProvider and define
 * the available triggers, actions, and conditions for the automation builder.
 */
final class NodeTypeRegistry
{
    /**
     * @var array<string, NodeTypeDefinition>
     */
    private array $types = [];

    /**
     * Register a node type definition.
     */
    public function register(NodeTypeDefinition $definition): self
    {
        $this->types[$definition->key] = $definition;

        return $this;
    }

    /**
     * Get a node type definition by key.
     */
    public function get(string $key): ?NodeTypeDefinition
    {
        return $this->types[$key] ?? null;
    }

    /**
     * Get a node type definition by key or throw.
     *
     * @throws InvalidArgumentException
     */
    public function getOrFail(string $key): NodeTypeDefinition
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Node type [{$key}] is not registered.");
    }

    /**
     * Check if a node type is registered.
     */
    public function has(string $key): bool
    {
        return isset($this->types[$key]);
    }

    /**
     * Get all registered node types.
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Get all triggers (node types with category 'trigger').
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function triggers(): array
    {
        return array_filter($this->types, fn (NodeTypeDefinition $def): bool => $def->category === 'trigger');
    }

    /**
     * Get all actions (node types with category 'action').
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function actions(): array
    {
        return array_filter($this->types, fn (NodeTypeDefinition $def): bool => $def->category === 'action');
    }

    /**
     * Get all conditions (node types with category 'condition').
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function conditions(): array
    {
        return array_filter($this->types, fn (NodeTypeDefinition $def): bool => $def->category === 'condition');
    }

    /**
     * Get node types that should appear in the palette.
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function paletteItems(): array
    {
        return array_filter($this->types, fn (NodeTypeDefinition $def): bool => $def->showInPalette && $def->category !== 'trigger');
    }

    /**
     * Get the Laravel event key for a trigger node type.
     */
    public function getEventKeyForTrigger(string $nodeType): ?string
    {
        $definition = $this->get($nodeType);
        if (! $definition || $definition->category !== 'trigger') {
            return null;
        }

        return $definition->eventKey;
    }

    /**
     * Find trigger types by their event key.
     *
     * @return array<string, NodeTypeDefinition>
     */
    public function findByEventKey(string $eventKey): array
    {
        return array_filter(
            $this->types,
            fn (NodeTypeDefinition $def): bool => $def->eventKey === $eventKey
        );
    }

    /**
     * Convert all definitions to array format for JSON serialization.
     *
     * @return array<string, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (NodeTypeDefinition $def): array => $def->toArray(),
            $this->types
        );
    }

    /**
     * Get definitions grouped by category.
     *
     * @return array{triggers: array<array<string, mixed>>, actions: array<array<string, mixed>>, conditions: array<array<string, mixed>>}
     */
    public function toGroupedArray(): array
    {
        return [
            'triggers' => array_values(array_map(fn (NodeTypeDefinition $d): array => $d->toArray(), $this->triggers())),
            'actions' => array_values(array_map(fn (NodeTypeDefinition $d): array => $d->toArray(), $this->actions())),
            'conditions' => array_values(array_map(fn (NodeTypeDefinition $d): array => $d->toArray(), $this->conditions())),
        ];
    }
}
