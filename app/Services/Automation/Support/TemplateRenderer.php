<?php

declare(strict_types=1);

namespace App\Services\Automation\Support;

final class TemplateRenderer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function render(mixed $value, array $data): mixed
    {
        if (is_string($value)) {
            return self::renderString($value, $data);
        }

        if (is_array($value)) {
            $rendered = [];
            foreach ($value as $key => $item) {
                $rendered[$key] = self::render($item, $data);
            }

            return $rendered;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function renderString(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($data): string {
            $path = $matches[1];
            $value = self::getByPath($data, $path);

            if ($value === null) {
                return '';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            return json_encode($value) ?: '';
        }, $template) ?? $template;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function getByPath(array $data, string $path): mixed
    {
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
