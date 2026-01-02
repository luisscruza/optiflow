import { ImagePlus, Loader2, X } from 'lucide-react';
import { useCallback, useState } from 'react';

import { cn } from '@/lib/utils';
import { type Media } from '@/types';

import { Button } from './button';

interface ImageUploadProps {
    value?: File[];
    onChange?: (files: File[]) => void;
    existingMedia?: Media[];
    onRemoveExisting?: (mediaId: number) => void;
    maxFiles?: number;
    maxSize?: number;
    accept?: string;
    disabled?: boolean;
    className?: string;
    error?: string;
}

export function ImageUpload({
    value = [],
    onChange,
    existingMedia = [],
    onRemoveExisting,
    maxFiles = 10,
    maxSize = 10 * 1024 * 1024, // 10MB
    accept = 'image/jpeg,image/png,image/gif,image/webp',
    disabled = false,
    className,
    error,
}: ImageUploadProps) {
    const [isDragOver, setIsDragOver] = useState(false);
    const [previewUrls, setPreviewUrls] = useState<Map<File, string>>(new Map());
    const [isLoading, setIsLoading] = useState(false);

    const totalFiles = value.length + existingMedia.length;
    const canAddMore = totalFiles < maxFiles;

    const createPreviewUrl = useCallback((file: File): string => {
        return URL.createObjectURL(file);
    }, []);

    const handleFiles = useCallback(
        (files: FileList | null) => {
            if (!files || disabled) return;

            setIsLoading(true);

            const newFiles: File[] = [];
            const newPreviews = new Map(previewUrls);

            Array.from(files).forEach((file) => {
                const acceptedTypes = accept.split(',').map((t) => t.trim());
                if (!acceptedTypes.includes(file.type)) {
                    console.warn(`File type ${file.type} not accepted`);
                    return;
                }

                if (file.size > maxSize) {
                    console.warn(`File ${file.name} exceeds max size`);
                    return;
                }

                if (value.length + newFiles.length + existingMedia.length >= maxFiles) {
                    console.warn('Max files limit reached');
                    return;
                }

                newFiles.push(file);
                newPreviews.set(file, createPreviewUrl(file));
            });

            setPreviewUrls(newPreviews);
            onChange?.([...value, ...newFiles]);
            setIsLoading(false);
        },
        [accept, createPreviewUrl, disabled, existingMedia.length, maxFiles, maxSize, onChange, previewUrls, value],
    );

    const handleDragOver = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            e.stopPropagation();
            if (!disabled && canAddMore) {
                setIsDragOver(true);
            }
        },
        [disabled, canAddMore],
    );

    const handleDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragOver(false);
    }, []);

    const handleDrop = useCallback(
        (e: React.DragEvent) => {
            e.preventDefault();
            e.stopPropagation();
            setIsDragOver(false);

            if (disabled || !canAddMore) return;

            handleFiles(e.dataTransfer.files);
        },
        [disabled, canAddMore, handleFiles],
    );

    const handleInputChange = useCallback(
        (e: React.ChangeEvent<HTMLInputElement>) => {
            handleFiles(e.target.files);
            e.target.value = '';
        },
        [handleFiles],
    );

    const handleRemoveNew = useCallback(
        (fileToRemove: File) => {
            const url = previewUrls.get(fileToRemove);
            if (url) {
                URL.revokeObjectURL(url);
                const newPreviews = new Map(previewUrls);
                newPreviews.delete(fileToRemove);
                setPreviewUrls(newPreviews);
            }

            onChange?.(value.filter((f) => f !== fileToRemove));
        },
        [onChange, previewUrls, value],
    );

    const handleRemoveExisting = useCallback(
        (mediaId: number) => {
            onRemoveExisting?.(mediaId);
        },
        [onRemoveExisting],
    );

    const formatFileSize = (bytes: number): string => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    return (
        <div className={cn('space-y-4', className)}>
            {canAddMore && (
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    className={cn(
                        'relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 transition-colors',
                        isDragOver && !disabled ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-muted-foreground/50',
                        disabled && 'cursor-not-allowed opacity-50',
                        error && 'border-destructive',
                    )}
                >
                    <input
                        type="file"
                        accept={accept}
                        multiple
                        onChange={handleInputChange}
                        disabled={disabled}
                        className="absolute inset-0 cursor-pointer opacity-0"
                    />
                    {isLoading ? (
                        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                    ) : (
                        <>
                            <ImagePlus className="mb-2 h-8 w-8 text-muted-foreground" />
                            <p className="text-sm text-muted-foreground">
                                <span className="font-medium text-primary">Haz clic para subir</span> o arrastra y suelta
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                PNG, JPG, GIF o WebP (máx. {formatFileSize(maxSize)})
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {totalFiles} de {maxFiles} imágenes
                            </p>
                        </>
                    )}
                </div>
            )}

            {error && <p className="text-sm text-destructive">{error}</p>}

            {(existingMedia.length > 0 || value.length > 0) && (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    {existingMedia.map((media) => (
                        <div key={media.id} className="group relative aspect-square overflow-hidden rounded-lg border bg-muted">
                            <img
                                src={media.preview_url || media.original_url}
                                alt={media.name}
                                className="h-full w-full object-cover"
                            />
                            {onRemoveExisting && !disabled && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    className="absolute right-1 top-1 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                    onClick={() => handleRemoveExisting(media.id)}
                                >
                                    <X className="h-3 w-3" />
                                </Button>
                            )}
                            <div className="absolute bottom-0 left-0 right-0 bg-black/50 px-2 py-1">
                                <p className="truncate text-xs text-white">{media.name}</p>
                            </div>
                        </div>
                    ))}

                    {/* New Files */}
                    {value.map((file, index) => {
                        const previewUrl = previewUrls.get(file);
                        return (
                            <div key={`new-${index}`} className="group relative aspect-square overflow-hidden rounded-lg border bg-muted">
                                {previewUrl ? (
                                    <img src={previewUrl} alt={file.name} className="h-full w-full object-cover" />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center">
                                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                                    </div>
                                )}
                                {!disabled && (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="icon"
                                        className="absolute right-1 top-1 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                        onClick={() => handleRemoveNew(file)}
                                    >
                                        <X className="h-3 w-3" />
                                    </Button>
                                )}
                                <div className="absolute bottom-0 left-0 right-0 bg-black/50 px-2 py-1">
                                    <p className="truncate text-xs text-white">{file.name}</p>
                                    <p className="text-xs text-white/70">{formatFileSize(file.size)}</p>
                                </div>
                                {/* New badge */}
                                <div className="absolute left-1 top-1 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-primary-foreground">
                                    Nuevo
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
