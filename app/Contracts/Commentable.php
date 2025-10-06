<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface Commentable
{
    public function comment(string $comment): Model;

    public function commentAsUser(?User $user, string $comment): Model;
}
