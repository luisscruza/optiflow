import { router } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useState } from 'react';

import { type BulkAction, type BulkConfirmationDialogState, type ConfirmationDialogState, type TableAction } from '../types';

interface UseDataTableActionsProps<T> {
    onAction?: (action: string, row: T) => void;
    handlers?: Record<string, (row: T) => void>;
    bulkHandlers?: Record<string, (selectedIds: (string | number)[]) => void>;
    selectedIds: (string | number)[];
    clearSelection: () => void;
}

interface UseDataTableActionsReturn<T> {
    confirmationDialog: ConfirmationDialogState<T>;
    bulkConfirmationDialog: BulkConfirmationDialogState;
    isDeleting: boolean;
    isBulkProcessing: boolean;
    handleActionClick: (action: TableAction, row: T) => void;
    handleConfirmAction: () => void;
    handleBulkAction: (action: BulkAction) => void;
    handleConfirmBulkAction: () => void;
    closeConfirmationDialog: () => void;
    closeBulkConfirmationDialog: () => void;
}

export function useDataTableActions<T>({
    onAction,
    handlers = {},
    bulkHandlers = {},
    selectedIds,
    clearSelection,
}: UseDataTableActionsProps<T>): UseDataTableActionsReturn<T> {
    const [confirmationDialog, setConfirmationDialog] = useState<ConfirmationDialogState<T>>({
        open: false,
        action: null,
        row: null,
    });
    const [isDeleting, setIsDeleting] = useState(false);

    const [bulkConfirmationDialog, setBulkConfirmationDialog] = useState<BulkConfirmationDialogState>({
        open: false,
        action: null,
    });
    const [isBulkProcessing, setIsBulkProcessing] = useState(false);

    const handleActionClick = useCallback(
        (action: TableAction, row: T) => {
            if (action.requiresConfirmation) {
                setConfirmationDialog({ open: true, action, row });
                return;
            }

            if (action.handler && handlers[action.handler]) {
                handlers[action.handler](row);
                return;
            }

            if (action.isCustom && onAction) {
                onAction(action.name, row);
                return;
            }

            if (action.name === 'delete' && action.href) {
                router.delete(action.href);
                return;
            }

            if (action.href && action.method === 'post') {
                router.post(action.href, {}, { preserveScroll: true });
                return;
            }

            if (action.href && action.method === 'get') {
                router.get(action.href, {}, { preserveScroll: true });
                return;
            }

            if (onAction) {
                onAction(action.name, row);
            }
        },
        [onAction, handlers],
    );

    const handleConfirmAction = useCallback(() => {
        const { action, row } = confirmationDialog;
        if (!action || !row) return;

        if (action.handler && handlers[action.handler]) {
            handlers[action.handler](row);
            setConfirmationDialog({ open: false, action: null, row: null });
            return;
        }

        if (action.isCustom && onAction) {
            onAction(action.name, row);
            setConfirmationDialog({ open: false, action: null, row: null });
            return;
        }

        if (action.name === 'delete' && action.href) {
            setIsDeleting(true);
            router.delete(action.href, {
                preserveScroll: true,
                onSuccess: () => {
                    setConfirmationDialog({ open: false, action: null, row: null });
                    setIsDeleting(false);
                },
                onError: () => {
                    setIsDeleting(false);
                },
            });
            return;
        }

        if (action.href && action.method === 'post') {
            router.post(
                action.href,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setConfirmationDialog({ open: false, action: null, row: null });
                    },
                },
            );
            return;
        }

        if (action.href && action.method === 'get') {
            router.get(
                action.href,
                {},
                {
                    preserveScroll: true,
                    onFinish: () => {
                        setConfirmationDialog({ open: false, action: null, row: null });
                    },
                },
            );
            return;
        }

        if (onAction) {
            onAction(action.name, row);
        }
        setConfirmationDialog({ open: false, action: null, row: null });
    }, [confirmationDialog, onAction, handlers]);

    const executeBulkAction = useCallback(
        (action: BulkAction) => {
            if (action.handler && bulkHandlers[action.handler]) {
                setIsBulkProcessing(true);
                Promise.resolve(bulkHandlers[action.handler](selectedIds)).finally(() => {
                    setIsBulkProcessing(false);
                    clearSelection();
                    setBulkConfirmationDialog({ open: false, action: null });
                });
                return;
            }

            if (action.href) {
                setIsBulkProcessing(true);

                axios
                    .post(
                        action.href,
                        { ids: selectedIds },
                        {
                            responseType: 'blob',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        },
                    )
                    .then((response) => {
                        const contentDisposition = response.headers['content-disposition'];
                        let filename = 'download.zip';

                        if (contentDisposition) {
                            const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
                            if (filenameMatch && filenameMatch[1]) {
                                filename = filenameMatch[1];
                            }
                        }

                        const url = window.URL.createObjectURL(new Blob([response.data]));
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        window.URL.revokeObjectURL(url);

                        setIsBulkProcessing(false);
                        clearSelection();
                        setBulkConfirmationDialog({ open: false, action: null });
                    })
                    .catch((error) => {
                        console.error('Bulk action error:', error);
                        setIsBulkProcessing(false);
                        setBulkConfirmationDialog({ open: false, action: null });
                    });
            }
        },
        [bulkHandlers, selectedIds, clearSelection],
    );

    const handleBulkAction = useCallback(
        (action: BulkAction) => {
            if (action.requiresConfirmation) {
                setBulkConfirmationDialog({ open: true, action });
                return;
            }
            executeBulkAction(action);
        },
        [executeBulkAction],
    );

    const handleConfirmBulkAction = useCallback(() => {
        const { action } = bulkConfirmationDialog;
        if (!action) return;
        executeBulkAction(action);
    }, [bulkConfirmationDialog, executeBulkAction]);

    const closeConfirmationDialog = useCallback(() => {
        if (!isDeleting) {
            setConfirmationDialog({ open: false, action: null, row: null });
        }
    }, [isDeleting]);

    const closeBulkConfirmationDialog = useCallback(() => {
        if (!isBulkProcessing) {
            setBulkConfirmationDialog({ open: false, action: null });
        }
    }, [isBulkProcessing]);

    return {
        confirmationDialog,
        bulkConfirmationDialog,
        isDeleting,
        isBulkProcessing,
        handleActionClick,
        handleConfirmAction,
        handleBulkAction,
        handleConfirmBulkAction,
        closeConfirmationDialog,
        closeBulkConfirmationDialog,
    };
}
