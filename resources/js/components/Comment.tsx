import React, { useState } from 'react';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ReplyIcon, MoreHorizontal, Edit2, Trash2, Loader2 } from 'lucide-react';
import { CommentForm } from './CommentForm';
import { MentionText } from './MentionText';
import comments from '@/routes/comments';

export interface CommentData {
    id: number;
    comment: string;
    created_at: string;
    edited_at?: string | null;
    commentator: {
        id: number;
        name: string;
        email: string;
    };
    comments?: CommentData[];
}

interface CommentProps {
    comment: CommentData;
    depth?: number;
    onReply?: (parentId: number) => void;
    replyingTo?: number | null;
    currentUser?: {
        id: number;
        name: string;
        email: string;
    };
    commentableType?: string;
    commentableId?: number;
    onReplySuccess?: () => void;
    onReplyCancel?: () => void;
}

export const Comment: React.FC<CommentProps> = ({
    comment,
    depth = 0,
    onReply,
    replyingTo,
    currentUser,
    commentableType,
    commentableId,
    onReplySuccess,
    onReplyCancel,
}) => {
    const [showReplies, setShowReplies] = useState(true);
    const [isEditing, setIsEditing] = useState(false);
    const maxDepth = 3; // Maximum nesting level for replies
    
    // Edit form
    const { data: editData, setData: setEditData, patch, processing: isUpdating, errors: editErrors, reset: resetEdit } = useForm({
        comment: comment.comment,
    });

    // Delete form
    const { delete: deleteComment, processing: isDeleting } = useForm();

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatTimeAgo = (dateString: string) => {
        try {
            return formatDistanceToNow(new Date(dateString), {
                addSuffix: true,
                locale: es,
            });
        } catch {
            return 'hace un momento';
        }
    };

    const handleEdit = () => {
        setIsEditing(true);
    };

    const handleCancelEdit = () => {
        setIsEditing(false);
        resetEdit();
        setEditData('comment', comment.comment);
    };

    const handleSaveEdit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!editData.comment.trim()) return;

        patch(comments.update.url({ comment: comment.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEditing(false);
            },
        });
    };

    const handleDelete = () => {
        if (confirm('¿Estás seguro de que quieres eliminar este comentario? Esta acción no se puede deshacer.')) {
            deleteComment(comments.destroy.url({ comment: comment.id }));
        }
    };

    const hasReplies = comment.comments && comment.comments.length > 0;
    const canReply = depth < maxDepth;
    const isOwner = currentUser?.id === comment.commentator.id;

    return (
        <div className={`group ${depth > 0 ? 'ml-8 border-l border-gray-200 pl-4' : ''}`}>
            <div className="flex gap-3">
                {/* Avatar */}
                <div className="flex-shrink-0">
                    <Avatar className="h-8 w-8">
                        <AvatarFallback className="bg-gray-100 text-gray-600 text-xs">
                            {getInitials(comment.commentator.name)}
                        </AvatarFallback>
                    </Avatar>
                </div>

                {/* Comment content */}
                <div className="flex-1 min-w-0">
                    {/* Comment header */}
                    <div className="flex items-center justify-between mb-1">
                        <div className="flex items-center gap-2">
                            <span className="font-medium text-gray-900 text-sm">
                                {comment.commentator.name}
                            </span>
                            <span className="text-gray-500 text-xs">
                                {formatTimeAgo(comment.created_at)}
                                {comment.edited_at && (
                                    <span 
                                        className="ml-1 text-gray-400 cursor-help" 
                                        title={`Editado ${formatTimeAgo(comment.edited_at)}`}
                                    >
                                        • editado
                                    </span>
                                )}
                            </span>
                        </div>
                        
                        {/* Edit/Delete dropdown - only show for comment owner */}
                        {isOwner && !isEditing && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="h-6 w-6 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                        disabled={isDeleting}
                                    >
                                        <MoreHorizontal className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={handleEdit}>
                                        <Edit2 className="h-4 w-4 mr-2" />
                                        Editar
                                    </DropdownMenuItem>
                                    <DropdownMenuItem 
                                        onClick={handleDelete}
                                        className="text-red-600 focus:text-red-600"
                                        disabled={isDeleting}
                                    >
                                        {isDeleting ? (
                                            <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        ) : (
                                            <Trash2 className="h-4 w-4 mr-2" />
                                        )}
                                        Eliminar
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>

                    {/* Comment text or edit form */}
                    {isEditing ? (
                        <form onSubmit={handleSaveEdit} className="mb-2">
                            <Textarea
                                value={editData.comment}
                                onChange={(e) => setEditData('comment', e.target.value)}
                                className="resize-none mb-2"
                                rows={3}
                                disabled={isUpdating}
                            />
                            {editErrors.comment && (
                                <p className="text-red-500 text-xs mb-2">{editErrors.comment}</p>
                            )}
                            <div className="flex gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={isUpdating || !editData.comment.trim()}
                                >
                                    {isUpdating && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                    Guardar
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleCancelEdit}
                                    disabled={isUpdating}
                                >
                                    Cancelar
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="text-gray-700 text-sm mb-2 whitespace-pre-wrap">
                            <MentionText text={comment.comment} />
                        </div>
                    )}

                    {/* Actions */}
                    {!isEditing && (
                        <div className="flex items-center gap-4">
                            {canReply && onReply && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onReply(comment.id)}
                                    className="h-auto p-0 text-gray-500 hover:text-gray-700 text-xs font-normal"
                                >
                                    <ReplyIcon className="h-3 w-3 mr-1" />
                                    Responder
                                </Button>
                            )}

                            {hasReplies && (
                                <button
                                    onClick={() => setShowReplies(!showReplies)}
                                    className="text-gray-500 hover:text-gray-700 text-xs"
                                >
                                    {showReplies ? 'Ocultar' : 'Mostrar'} {comment.comments!.length} respuesta{comment.comments!.length !== 1 ? 's' : ''}
                                </button>
                            )}
                        </div>
                    )}

                    {/* Reply form */}
                    {replyingTo === comment.id && currentUser && commentableType && commentableId !== undefined && (
                        <div className="mt-3">
                            <CommentForm
                                commentableType={commentableType}
                                commentableId={commentableId}
                                parentId={comment.id}
                                parentCommentId={comment.id}
                                currentUser={currentUser}
                                placeholder="Escribe una respuesta..."
                                showCancel={true}
                                onSuccess={onReplySuccess}
                                onCancel={onReplyCancel}
                            />
                        </div>
                    )}

                    {/* Nested replies */}
                    {hasReplies && showReplies && (
                        <div className="mt-3 space-y-3">
                            {comment.comments!.map((reply) => (
                                <Comment
                                    key={reply.id}
                                    comment={reply}
                                    depth={depth + 1}
                                    onReply={onReply}
                                    replyingTo={replyingTo}
                                    currentUser={currentUser}
                                    commentableType={commentableType}
                                    commentableId={commentableId}
                                    onReplySuccess={onReplySuccess}
                                    onReplyCancel={onReplyCancel}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};