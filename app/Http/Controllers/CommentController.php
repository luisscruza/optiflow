<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateCommentAction;
use App\Actions\DeleteCommentAction;
use App\Actions\UpdateCommentAction;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, CreateCommentAction $action, #[CurrentUser] \App\Models\User $user): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return redirect()->back()->with('success', 'Comentario agregado exitosamente.');
    }

    public function update(UpdateCommentRequest $request, Comment $comment, UpdateCommentAction $action, #[CurrentUser] $user): RedirectResponse
    {
        // Check if the user owns the comment
        if ($comment->user_id !== $user->id) {
            abort(403, 'No tienes permisos para editar este comentario.');
        }

        $action->handle($comment, $request->validated('comment'));

        return redirect()->back()->with('success', 'Comentario actualizado exitosamente.');
    }

    public function destroy(Comment $comment, DeleteCommentAction $action, #[CurrentUser] $user): RedirectResponse
    {
        // Check if the user owns the comment
        if ($comment->user_id !== $user->id) {
            abort(403, 'No tienes permisos para eliminar este comentario.');
        }

        $action->handle($comment);

        return redirect()->back()->with('success', 'Comentario eliminado exitosamente.');
    }
}
