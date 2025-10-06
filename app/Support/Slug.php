<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * @param  class-string<Model>  $model
     */
    public static function generateUniqueSlug(string $name, string $model): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;

        $slugs = $model::where('slug', 'LIKE', "{$baseSlug}%")
            ->pluck('slug')
            ->toArray();

        if (! in_array($baseSlug, $slugs)) {
            return $baseSlug;
        }

        $counter = 1;
        while (in_array($slug, $slugs)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;

    }
}
