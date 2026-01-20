<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Commentable;
use App\Models\User;
use App\Services\MentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateCommentAction
{
    public function __construct(
        private MentionService $mentionService
    ) {}

    /**
     * Execute the action.
     */
    public function handle(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            /** @var class-string<Model&Commentable> $class */
            $class = $this->resolveCommentableClass($data['commentable_type']);

            /** @var Model&Commentable $model */
            $model = $class::query()->findOrFail($data['commentable_id']);

            $comment = $model->comment($data['comment']);

            // Process mentions and send notifications
            $mentionedUserIds = $data['mentioned_user_ids'] ?? [];
            $this->mentionService->processMentionsWithIds($comment, $user, $mentionedUserIds);
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
