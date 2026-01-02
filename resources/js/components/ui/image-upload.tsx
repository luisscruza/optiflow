import { ChevronDown, ChevronLeft, ChevronRight, ImagePlus, Loader2, Plus, X, ZoomIn } from 'lucide-react';
import { useCallback, useState } from 'react';

import { cn } from '@/lib/utils';
import { type Media } from '@/types';

import { Button } from './button';
import { Dialog, DialogContent } from './dialog';

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
    dropzoneCollapsible?: boolean;
    dropzoneDefaultOpen?: boolean;
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
    dropzoneCollapsible = false,
    dropzoneDefaultOpen = true,
}: ImageUploadProps) {
    const [isDragOver, setIsDragOver] = useState(false);
    const [previewUrls, setPreviewUrls] = useState<Map<File, string>>(new Map());
    const [isLoading, setIsLoading] = useState(false);
    const [dropzoneOpen, setDropzoneOpen] = useState(dropzoneDefaultOpen);
    
    // Lightbox state
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(0);
    const [lightboxType, setLightboxType] = useState<'existing' | 'new'>('existing');

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

    // Lightbox handlers
    const openLightbox = (index: number, type: 'existing' | 'new') => {
        setLightboxIndex(index);
        setLightboxType(type);
        setLightboxOpen(true);
    };

    const getLightboxImage = (): { url: string; name: string } | null => {
        if (lightboxType === 'existing' && existingMedia[lightboxIndex]) {
            return {
                url: existingMedia[lightboxIndex].original_url,
                name: existingMedia[lightboxIndex].name,
            };
        }
        if (lightboxType === 'new' && value[lightboxIndex]) {
            const previewUrl = previewUrls.get(value[lightboxIndex]);
            return previewUrl ? { url: previewUrl, name: value[lightboxIndex].name } : null;
        }
        return null;
    };

    const navigateLightbox = (direction: 'prev' | 'next') => {
        const totalExisting = existingMedia.length;
        const totalNew = value.length;
        
        if (lightboxType === 'existing') {
            if (direction === 'next') {
                if (lightboxIndex < totalExisting - 1) {
                    setLightboxIndex(lightboxIndex + 1);
                } else if (totalNew > 0) {
                    setLightboxType('new');
                    setLightboxIndex(0);
                }
            } else {
                if (lightboxIndex > 0) {
                    setLightboxIndex(lightboxIndex - 1);
                }
            }
        } else {
            if (direction === 'next') {
                if (lightboxIndex < totalNew - 1) {
                    setLightboxIndex(lightboxIndex + 1);
                }
            } else {
                if (lightboxIndex > 0) {
                    setLightboxIndex(lightboxIndex - 1);
                } else if (totalExisting > 0) {
                    setLightboxType('existing');
                    setLightboxIndex(totalExisting - 1);
                }
            }
        }
    };

    const canNavigatePrev = lightboxType === 'existing' ? lightboxIndex > 0 : (lightboxIndex > 0 || existingMedia.length > 0);
    const canNavigateNext = lightboxType === 'existing' 
        ? (lightboxIndex < existingMedia.length - 1 || value.length > 0) 
        : lightboxIndex < value.length - 1;

    const lightboxImage = getLightboxImage();

    return (
        <div className={cn('space-y-4', className)}>
            {/* Lightbox Dialog */}
            <Dialog open={lightboxOpen} onOpenChange={setLightboxOpen}>
                <DialogContent className="max-w-4xl bg-black/95 p-0 border-0" showCloseButton={false}>
                    <div className="relative flex items-center justify-center min-h-[60vh] max-h-[90vh]">
                        {/* Close button */}
                        <Button
                            variant="ghost"
                            size="icon"
                            className="absolute right-2 top-2 z-10 h-8 w-8 text-white hover:bg-white/20"
                            onClick={() => setLightboxOpen(false)}
                        >
                            <X className="h-5 w-5" />
                        </Button>

                        {/* Navigation - Previous */}
                        {canNavigatePrev && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="absolute left-2 z-10 h-10 w-10 text-white hover:bg-white/20"
                                onClick={() => navigateLightbox('prev')}
                            >
                                <ChevronLeft className="h-6 w-6" />
                            </Button>
                        )}

                        {/* Image */}
                        {lightboxImage && (
                            <img
                                src={lightboxImage.url}
                                alt={lightboxImage.name}
                                className="max-h-[85vh] max-w-full object-contain"
                            />
                        )}

                        {/* Navigation - Next */}
                        {canNavigateNext && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="absolute right-2 z-10 h-10 w-10 text-white hover:bg-white/20"
                                onClick={() => navigateLightbox('next')}
                            >
                                <ChevronRight className="h-6 w-6" />
                            </Button>
                        )}

                        {/* Image name */}
                        {lightboxImage && (
                            <div className="absolute bottom-0 left-0 right-0 bg-black/50 px-4 py-2">
                                <p className="text-center text-sm text-white">{lightboxImage.name}</p>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            {canAddMore && dropzoneCollapsible && (
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setDropzoneOpen(!dropzoneOpen)}
                    className="w-full"
                >
                    {dropzoneOpen ? (
                        <>
                            <ChevronDown className="mr-2 h-4 w-4" />
                            Ocultar zona de subida
                        </>
                    ) : (
                        <>
                            <Plus className="mr-2 h-4 w-4" />
                            Agregar imágenes
                        </>
                    )}
                </Button>
            )}

            {canAddMore && (!dropzoneCollapsible || dropzoneOpen) && (
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
                    {existingMedia.map((media, index) => (
                        <div key={media.id} className="group relative aspect-square overflow-hidden rounded-lg border bg-muted">
                            <img
                                src={media.preview_url || media.original_url}
                                alt={media.name}
                                className="h-full w-full cursor-pointer object-cover transition-transform hover:scale-105"
                                onClick={() => openLightbox(index, 'existing')}
                            />
                            {/* Zoom icon overlay */}
                            <button
                                type="button"
                                className="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all group-hover:bg-black/20 group-hover:opacity-100"
                                onClick={() => openLightbox(index, 'existing')}
                            >
                                <ZoomIn className="h-8 w-8 text-white drop-shadow-lg" />
                            </button>
                            {onRemoveExisting && !disabled && (
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="icon"
                                    className="absolute right-1 top-1 z-10 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        handleRemoveExisting(media.id);
                                    }}
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
                                    <>
                                        <img
                                            src={previewUrl}
                                            alt={file.name}
                                            className="h-full w-full cursor-pointer object-cover transition-transform hover:scale-105"
                                            onClick={() => openLightbox(index, 'new')}
                                        />
                                        {/* Zoom icon overlay */}
                                        <button
                                            type="button"
                                            className="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all group-hover:bg-black/20 group-hover:opacity-100"
                                            onClick={() => openLightbox(index, 'new')}
                                        >
                                            <ZoomIn className="h-8 w-8 text-white drop-shadow-lg" />
                                        </button>
                                    </>
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
                                        className="absolute right-1 top-1 z-10 h-6 w-6 opacity-0 transition-opacity group-hover:opacity-100"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleRemoveNew(file);
                                        }}
                                    >
                                        <X className="h-3 w-3" />
                                    </Button>
                                )}
                                <div className="absolute bottom-0 left-0 right-0 bg-black/50 px-2 py-1">
                                    <p className="truncate text-xs text-white">{file.name}</p>
                                    <p className="text-xs text-white/70">{formatFileSize(file.size)}</p>
                                </div>
                                {/* New badge */}
                                <div className="absolute left-1 top-1 z-10 rounded bg-primary px-1.5 py-0.5 text-xs font-medium text-primary-foreground">
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
