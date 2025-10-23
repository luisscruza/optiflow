<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentMention;
use Illuminate\Support\Collection;

final class MentionService
{
    /**
     * Extract mentions from comment text
     * Returns array of usernames (without @)
     */
    public function extractMentions(string $text): array
    {
        preg_match_all('/@([a-zA-Z0-9._-]+)/', $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Find users by usernames
     */
    public function findMentionedUsers(array $usernames): Collection
    {
        if ($usernames === []) {
            return collect();
        }

        return User::query()->whereLike('name', $usernames)->get();
    }

    /**
     * Send mention notifications
     */
    public function sendMentionNotifications(Comment $comment, User $mentioner): void
    {
        $mentionedUsernames = $this->extractMentions($comment->comment);

        if ($mentionedUsernames === []) {
            return;
        }

        $mentionedUsers = $this->findMentionedUsers($mentionedUsernames);

        // Don't notify the person who made the comment
        $mentionedUsers = $mentionedUsers->reject(fn (User $user): bool => $user->id === $mentioner->id);

        foreach ($mentionedUsers as $user) {
            $user->notify(new CommentMention($comment, $mentioner));
        }
    }

    /**
     * Process mentions when a comment is created
     */
    public function processMentions(Comment $comment, User $author): void
    {
        $this->sendMentionNotifications($comment, $author);
    }

    /**
     * Highlight mentions in text for frontend display
     */
    public function highlightMentions(string $text): string
    {
        return preg_replace(
            '/@([a-zA-Z0-9._-]+)/',
            '<span class="mention">@$1</span>',
            $text
        );
    }

    /**
     * Get users for autocomplete (for current workspace)
     */
    public function getUsersForAutocomplete(?int $workspaceId = null): Collection
    {
        $query = User::query()->select(['id', 'name', 'email']);

        if ($workspaceId !== null && $workspaceId !== 0) {
            $query->whereHas('workspaces', function ($q) use ($workspaceId): void {
                $q->where('workspace_id', $workspaceId);
            });
        }

        return $query->orderBy('name')->get();
    }
}
