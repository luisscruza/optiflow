<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class UserInvitation extends Model
{
    use Notifiable;

    /**
     * Generate a unique token for the invitation.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(60);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    /**
     * Mark the invitation as accepted.
     */
    public function accept(User $user): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'user_id' => $user->id,
        ]);
    }

    /**
     * Mark the invitation as declined.
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    /**
     * Set expiration to 7 days from now.
     */
    public function setDefaultExpiration(): void
    {
        $this->expires_at = Carbon::now()->addDays(7);
    }

    /**
     * Get the workspace this invitation belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Get the user who accepted the invitation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
