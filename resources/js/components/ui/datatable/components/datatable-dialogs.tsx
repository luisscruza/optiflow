import * as React from 'react';

import { Button } from '../../button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '../../dialog';
import { type BulkAction, type BulkConfirmationDialogState, type ConfirmationDialogState, type TableAction } from '../types';

interface ConfirmationDialogProps<T> {
    state: ConfirmationDialogState<T>;
    isProcessing: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export function ConfirmationDialog<T>({ state, isProcessing, onClose, onConfirm }: ConfirmationDialogProps<T>) {
    return (
        <Dialog
            open={state.open}
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar acción</DialogTitle>
                    <DialogDescription>
                        {state.action?.confirmationMessage || '¿Estás seguro de que deseas realizar esta acción?'}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isProcessing}>
                        Cancelar
                    </Button>
                    <Button
                        variant={state.action?.color === 'danger' ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={isProcessing}
                    >
                        {isProcessing ? 'Procesando...' : 'Confirmar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface BulkConfirmationDialogProps {
    state: BulkConfirmationDialogState;
    selectedCount: number;
    isProcessing: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export const BulkConfirmationDialog: React.FC<BulkConfirmationDialogProps> = ({
    state,
    selectedCount,
    isProcessing,
    onClose,
    onConfirm,
}) => {
    return (
        <Dialog
            open={state.open}
            onOpenChange={(open) => {
                if (!open) onClose();
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Confirmar acción masiva</DialogTitle>
                    <DialogDescription>
                        {state.action?.confirmationMessage ||
                            `¿Estás seguro de que deseas realizar esta acción en ${selectedCount} elemento${selectedCount !== 1 ? 's' : ''}?`}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isProcessing}>
                        Cancelar
                    </Button>
                    <Button
                        variant={state.action?.color === 'danger' ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={isProcessing}
                    >
                        {isProcessing ? 'Procesando...' : 'Confirmar'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};
