import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import comments from '@/routes/comments';
import { useForm } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import React from 'react';
import { MentionTextarea } from './MentionTextarea';

interface CommentFormProps {
    commentableType: string;
    commentableId: number | string;
    parentId?: number;
    parentCommentId?: number; // When replying to a comment, this is the comment ID
    onSuccess?: () => void;
    onCancel?: () => void;
    placeholder?: string;
    showCancel?: boolean;
    currentUser?: {
        id: number;
        name: string;
        email: string;
    };
}

export const CommentForm: React.FC<CommentFormProps> = ({
    commentableType,
    commentableId,
    parentId,
    parentCommentId,
    onSuccess,
    onCancel,
    placeholder = 'Escribe un comentario...',
    showCancel = false,
    currentUser,
}) => {
    // When replying to a comment, the commentable should be the parent comment
    const actualCommentableType = parentCommentId ? 'Comment' : commentableType;
    const actualCommentableId = parentCommentId ? parentCommentId : commentableId;

    const { data, setData, post, processing, errors, reset } = useForm({
        comment: '',
        commentable_type: actualCommentableType,
        commentable_id: actualCommentableId,
        parent_id: parentId,
        mentioned_user_ids: [] as number[],
    });

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((word) => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const handleMentionChange = (mentionedUserIds: number[]) => {
        setData('mentioned_user_ids', mentionedUserIds);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!data.comment.trim()) return;

        post(comments.store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                reset('comment');
                setData('mentioned_user_ids', []);
                onSuccess?.();
            },
        });
    };

    const handleCancel = () => {
        reset('comment');
        onCancel?.();
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <div className="flex gap-3">
                {currentUser && (
                    <div className="flex-shrink-0">
                        <Avatar className="h-8 w-8">
                            <AvatarFallback className="bg-gray-100 text-xs text-gray-600">{getInitials(currentUser.name)}</AvatarFallback>
                        </Avatar>
                    </div>
                )}

                <div className="flex-1">
                    <MentionTextarea
                        value={data.comment}
                        onChange={(value) => setData('comment', value)}
                        onMentionChange={handleMentionChange}
                        placeholder={placeholder}
                        rows={3}
                        disabled={processing}
                    />

                    {errors.comment && <p className="mt-1 text-xs text-red-500">{errors.comment}</p>}
                </div>
            </div>

            <div className="flex justify-end gap-2">
                {showCancel && (
                    <Button type="button" variant="outline" size="sm" onClick={handleCancel} disabled={processing}>
                        Cancelar
                    </Button>
                )}

                <Button type="submit" size="sm" disabled={processing || !data.comment.trim()}>
                    {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                    {parentId ? 'Responder' : 'Comentar'}
                </Button>
            </div>
        </form>
    );
};
