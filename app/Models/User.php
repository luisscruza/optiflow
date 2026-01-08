<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property UserRole $business_role
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property int|null $current_workspace_id
 * @property-read Workspace|null $currentWorkspace
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Workspace> $ownedWorkspaces
 * @property-read int|null $owned_workspaces_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Workspace> $workspaces
 * @property-read int|null $workspaces_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserInvitation> $receivedInvitations
 * @property-read int|null $received_invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserInvitation> $sentInvitations
 * @property-read int|null $sent_invitations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBusinessRole($value)
 *
 * @mixin \Eloquent
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the workspaces that belong to the user.
     *
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the user's current workspace.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * Safely get the current workspace without throwing an exception.
     * Returns null if current_workspace_id is not set or not loaded.
     */
    public function getCurrentWorkspaceSafely(): ?Workspace
    {
        if (! array_key_exists('current_workspace_id', $this->attributes)) {
            return null;
        }

        if ($this->current_workspace_id === null) {
            return null;
        }

        return $this->currentWorkspace;
    }

    /**
     * Get the workspaces owned by the user.
     *
     * @return HasMany<Workspace, $this>
     */
    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    /**
     * Get the invitations sent by this user.
     *
     * @return HasMany<UserInvitation, $this>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'invited_by');
    }

    /**
     * Get the invitations received by this user.
     *
     * @return HasMany<UserInvitation, $this>
     */
    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(UserInvitation::class, 'user_id');
    }

    /**
     * Check if user has access to a workspace.
     */
    public function hasAccessToWorkspace(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspace_id', $workspace->id)->exists();
    }

    /**
     * Switch to a workspace if user has access.
     */
    public function switchToWorkspace(Workspace $workspace): bool
    {
        if (! $this->hasAccessToWorkspace($workspace)) {
            return false;
        }

        $this->current_workspace_id = $workspace->id;
        $this->save();

        return true;
    }

    /**
     * Check if user has a specific business role.
     */
    public function hasBusinessRole(UserRole $role): bool
    {
        return $this->business_role === $role;
    }

    /**
     * Whether the user must change password on next login.
     */
    public function mustChangePassword(): bool
    {
        return $this->password_changed_at === null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'business_role' => UserRole::class,
            'dashboard_layout' => 'array',
            'password_changed_at' => 'datetime',
        ];
    }
}
