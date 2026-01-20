<?php

declare(strict_types=1);

namespace App\Services\Automation\NodeRunners;

use App\Services\Automation\Support\AutomationContext;
use App\Services\Automation\Support\NodeResult;
use App\Services\Automation\Support\TemplateRenderer;

final class ConditionNodeRunner implements AutomationNodeRunner
{
    public function type(): string
    {
        return 'logic.condition';
    }

    public function run(AutomationContext $context, array $config, array $input): NodeResult
    {
        $field = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? '';

        $templateData = $context->toTemplateData($input);

        // Render the field path to get actual value
        $actualValue = $this->getValueByPath($templateData, $field);

        // Render the comparison value in case it has template variables
        $compareValue = is_string($value)
            ? TemplateRenderer::renderString($value, $templateData)
            : $value;

        $result = $this->evaluate($actualValue, $operator, $compareValue);

        return NodeResult::success([
            'condition_result' => $result,
            'branch' => $result ? 'true' : 'false',
            'evaluated' => [
                'field' => $field,
                'actual_value' => $actualValue,
                'operator' => $operator,
                'compare_value' => $compareValue,
            ],
        ]);
    }

    private function evaluate(mixed $actualValue, string $operator, mixed $compareValue): bool
    {
        return match ($operator) {
            'equals', '==' => $actualValue === $compareValue,
            'not_equals', '!=' => $actualValue !== $compareValue,
            'contains' => is_string($actualValue) && is_string($compareValue) && str_contains($actualValue, $compareValue),
            'not_contains' => is_string($actualValue) && is_string($compareValue) && ! str_contains($actualValue, $compareValue),
            'starts_with' => is_string($actualValue) && is_string($compareValue) && str_starts_with($actualValue, $compareValue),
            'ends_with' => is_string($actualValue) && is_string($compareValue) && str_ends_with($actualValue, $compareValue),
            'greater_than', '>' => is_numeric($actualValue) && is_numeric($compareValue) && $actualValue > $compareValue,
            'less_than', '<' => is_numeric($actualValue) && is_numeric($compareValue) && $actualValue < $compareValue,
            'greater_or_equal', '>=' => is_numeric($actualValue) && is_numeric($compareValue) && $actualValue >= $compareValue,
            'less_or_equal', '<=' => is_numeric($actualValue) && is_numeric($compareValue) && $actualValue <= $compareValue,
            'is_empty' => empty($actualValue),
            'is_not_empty' => ! empty($actualValue),
            'is_null' => $actualValue === null,
            'is_not_null' => $actualValue !== null,
            'in_list' => $this->inList($actualValue, $compareValue),
            'not_in_list' => ! $this->inList($actualValue, $compareValue),
            'regex' => is_string($actualValue) && is_string($compareValue) && (bool) preg_match($compareValue, $actualValue),
            default => false,
        };
    }

    private function inList(mixed $value, mixed $list): bool
    {
        if (is_string($list)) {
            $list = array_map('trim', explode(',', $list));
        }

        if (! is_array($list)) {
            return false;
        }

        return in_array($value, $list, false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function getValueByPath(array $data, string $path): mixed
    {
        // Remove {{ }} if present
        $path = mb_trim($path, '{}');
        $path = mb_trim($path);

        $segments = explode('.', $path);
        $current = $data;

        foreach ($segments as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
