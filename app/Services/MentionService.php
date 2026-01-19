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
     * Returns array of usernames (without @ and brackets)
     * Supports: @username and @[Full Name]
     */
    public function extractMentions(string $text): array
    {
        // Match @[Full Name] (with brackets for multi-word names)
        preg_match_all('/@\[([^\]]+)\]/', $text, $bracketMatches);

        // Match @username (single word without brackets)
        preg_match_all('/@([a-zA-Z0-9._-]+)(?!\[)/', $text, $simpleMatches);

        $mentions = array_merge(
            $bracketMatches[1] ?? [],
            $simpleMatches[1] ?? []
        );

        return array_unique($mentions);
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
     * Process mentions using user IDs directly (more reliable)
     */
    public function processMentionsWithIds(Comment $comment, User $author, array $mentionedUserIds): void
    {
        if ($mentionedUserIds === []) {
            return;
        }

        $mentionedUsers = User::query()
            ->whereIn('id', $mentionedUserIds)
            ->where('id', '!=', $author->id)
            ->get();

        foreach ($mentionedUsers as $user) {
            $user->notify(new CommentMention($comment, $author));
        }
    }

    /**
     * Highlight mentions in text for frontend display
     * Supports: @username and @[Full Name]
     * Removes brackets when rendering
     */
    public function highlightMentions(string $text): string
    {
        // First, highlight @[Full Name] mentions (removing brackets)
        $text = preg_replace(
            '/@\[([^\]]+)\]/',
            '<span class="mention">@$1</span>',
            $text
        );

        // Then, highlight @username mentions (but not already wrapped ones)
        $text = preg_replace(
            '/@([a-zA-Z0-9._-]+)(?!\[)/',
            '<span class="mention">@$1</span>',
            $text
        );

        return $text;
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
