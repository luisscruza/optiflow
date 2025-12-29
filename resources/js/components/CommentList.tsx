import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { MessageCircle } from 'lucide-react';
import React, { useState } from 'react';
import { Comment, type CommentData } from './Comment';
import { CommentForm } from './CommentForm';

interface CommentListProps extends React.HTMLAttributes<HTMLDivElement> {
    comments: CommentData[];
    commentableType: string;
    commentableId: number | string;
    currentUser?: {
        id: number;
        name: string;
        email: string;
    };
    title?: string;
    showTitle?: boolean;
}

export const CommentList: React.FC<CommentListProps> = ({
    comments,
    commentableType,
    commentableId,
    currentUser,
    title = 'Comentarios',
    showTitle = true,
    className,
    ...props
}) => {
    const [replyingTo, setReplyingTo] = useState<number | null>(null);

    const handleReply = (parentId: number) => {
        setReplyingTo(replyingTo === parentId ? null : parentId);
    };

    const handleReplySuccess = () => {
        setReplyingTo(null);
        // In a real app, you might want to refresh the comments here
        // or handle optimistic updates
    };

    const handleReplyCancel = () => {
        setReplyingTo(null);
    };

    // Sort comments to show newest first for top-level comments
    const sortedComments = [...comments].sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

    return (
        <Card className={cn(className)} {...props}>
            {showTitle && (
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <MessageCircle className="h-5 w-5" />
                        {title}
                        {comments.length > 0 && <span className="text-sm font-normal text-gray-500">({comments.length})</span>}
                    </CardTitle>
                </CardHeader>
            )}

            <CardContent className="space-y-6">
                {/* Main comment form */}
                <CommentForm
                    commentableType={commentableType}
                    commentableId={commentableId}
                    currentUser={currentUser}
                    placeholder="Añadir un comentario..."
                />

                {/* Comments list */}
                {sortedComments.length > 0 ? (
                    <div className="space-y-4 border-t pt-4">
                        {sortedComments.map((comment) => (
                            <div key={comment.id}>
                                <Comment
                                    comment={comment}
                                    onReply={handleReply}
                                    replyingTo={replyingTo}
                                    currentUser={currentUser}
                                    commentableType={commentableType}
                                    commentableId={commentableId}
                                    onReplySuccess={handleReplySuccess}
                                    onReplyCancel={handleReplyCancel}
                                />
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="border-t py-8 text-center text-gray-500">
                        <MessageCircle className="mx-auto mb-3 h-12 w-12 text-gray-300" />
                        <p>No hay comentarios aún.</p>
                        <p className="text-sm">Sé el primero en comentar.</p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
};
