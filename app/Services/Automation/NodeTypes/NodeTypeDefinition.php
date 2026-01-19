<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeTypes;

/**
 * Represents a node type definition for the automation builder.
 */
final readonly class NodeTypeDefinition
{
    /**
     * @param  string  $key  Unique identifier (e.g., 'workflow.stage_entered', 'http.webhook')
     * @param  string  $category  Category: 'trigger', 'action', 'condition'
     * @param  string  $label  Display label for the node
     * @param  string  $description  Short description of what this node does
     * @param  string  $icon  Lucide icon name (e.g., 'Zap', 'Webhook', 'Send')
     * @param  string  $color  Tailwind color name (e.g., 'amber', 'blue', 'green')
     * @param  string  $reactNodeType  React Flow node component type
     * @param  array<string, mixed>  $defaultConfig  Default configuration values
     * @param  bool  $allowMultiple  Whether multiple instances can be added
     * @param  bool  $showInPalette  Whether to show in the add-node palette
     * @param  string|null  $eventKey  For triggers: the Laravel event key this maps to
     * @param  string|null  $inspectorComponent  Custom inspector component name
     * @param  array<string, array{type: string, description: string, children?: array<string, mixed>}>  $outputSchema  Schema describing the data this node outputs
     */
    public function __construct(
        public string $key,
        public string $category,
        public string $label,
        public string $description,
        public string $icon,
        public string $color,
        public string $reactNodeType,
        public array $defaultConfig = [],
        public bool $allowMultiple = true,
        public bool $showInPalette = true,
        public ?string $eventKey = null,
        public ?string $inspectorComponent = null,
        public array $outputSchema = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'category' => $this->category,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'reactNodeType' => $this->reactNodeType,
            'defaultConfig' => $this->defaultConfig,
            'allowMultiple' => $this->allowMultiple,
            'showInPalette' => $this->showInPalette,
            'eventKey' => $this->eventKey,
            'inspectorComponent' => $this->inspectorComponent,
            'outputSchema' => $this->outputSchema,
        ];
    }
}
