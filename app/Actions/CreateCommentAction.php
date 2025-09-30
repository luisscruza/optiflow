<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Commentable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateCommentAction
{
    /**
     * Execute the action.
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($data): void {
            /** @var class-string<Commentable> $class */
            $class = $this->resolveCommentableClass($data['commentable_type']);

            /** @var Commentable $model */
            $model = $class::findOrFail($data['commentable_id']);
            $model->comment($data['comment']);
        });
    }

    /**
     * Resolves the fully qualified class name of the commentable model.
     *
     * @return class-string<Commentable>
     */
    private function resolveCommentableClass(string $type): string
    {
        $fqcn = "App\\Models\\{$type}";

        if (! is_subclass_of($fqcn, Commentable::class)) {
            throw new InvalidArgumentException("{$fqcn} is not commentable.");
        }

        return $fqcn;
    }
}
