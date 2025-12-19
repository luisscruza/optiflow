<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BusinessPermission;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\UnauthorizedException;

final class Impersonator
{
    private string $sessionKey;

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Session $session
    ) {
        $this->sessionKey = 'impersonator'.sha1(self::class);
    }

    /**
     * @throws UnauthorizedException
     */
    public function start(User $targetUser, string $guard = 'web'): void
    {
        $guard = $this->auth->guard($guard);

        $currentUser = $guard->user();

        if (! $currentUser) {
            throw new UnauthorizedException('You must be logged in to impersonate.');
        }

        if ($currentUser->is($targetUser)) {
            throw new UnauthorizedException('You cannot impersonate yourself.');
        }

        if (! Gate::allows(BusinessPermission::Impersonate->value, $currentUser)) {
            throw new UnauthorizedException('You are not allowed to impersonate.');
        }

        if ($this->isImpersonating()) {
            throw new UnauthorizedException('You are already impersonating a user.');
        }

        $this->session->put($this->sessionKey, $currentUser->id);

        $this->auth->login($targetUser);

    }

    public function stop(): void
    {
        $originalUserId = $this->session->pull($this->sessionKey);

        if (! $originalUserId) {
            return;
        }

        $this->auth->loginUsingId($originalUserId);
    }

    public function isImpersonating(): bool
    {
        return $this->session->has($this->sessionKey);
    }

    public function getImpersonatorId(): ?int
    {
        return $this->session->get($this->sessionKey);
    }
}
