import { Head, router, useForm } from '@inertiajs/react';
import { Edit, Plus, Save, Trash2, X } from 'lucide-react';
import { useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Mastertable, type MastertableItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración',
        href: '/configuration',
    },
    {
        title: 'Tablas maestras',
        href: '/mastertables',
    },
];

interface Props {
    mastertable: Mastertable & { items: MastertableItem[]; items_count: number };
}

export default function ShowMastertable({ mastertable }: Props) {
    const { can } = usePermissions();
    const [addingItem, setAddingItem] = useState(false);
    const [editingItemId, setEditingItemId] = useState<number | null>(null);

    const {
        data: newItemData,
        setData: setNewItemData,
        post: postNewItem,
        processing: processingNewItem,
        errors: newItemErrors,
        reset: resetNewItem,
    } = useForm({
        name: '',
    });

    const {
        data: editItemData,
        setData: setEditItemData,
        put: putEditItem,
        processing: processingEditItem,
        errors: editItemErrors,
    } = useForm({
        name: '',
    });

    const handleAddItem = (e: React.FormEvent) => {
        e.preventDefault();
        postNewItem(`/mastertables/${mastertable.id}/items`, {
            onSuccess: () => {
                resetNewItem();
                setAddingItem(false);
            },
        });
    };

    const handleEditItem = (item: MastertableItem) => {
        setEditingItemId(item.id);
        setEditItemData('name', item.name);
    };

    const handleUpdateItem = (itemId: number) => {
        putEditItem(`/mastertables/${mastertable.id}/items/${itemId}`, {
            onSuccess: () => {
                setEditingItemId(null);
            },
        });
    };

    const handleDeleteItem = (itemId: number) => {
        if (confirm('¿Está seguro de que desea eliminar este elemento?')) {
            router.delete(`/mastertables/${mastertable.id}/items/${itemId}`);
        }
    };

    const handleEdit = () => {
        router.visit(`/mastertables/${mastertable.id}/edit`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mastertable.name} />

            <div className="max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-8 flex items-start justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{mastertable.name}</h1>
                        <div className="mt-2 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>
                                Alias: <span className="font-mono text-gray-900 dark:text-gray-100">{mastertable.alias}</span>
                            </span>
                            <span>•</span>
                            <span>{mastertable.items_count} elementos</span>
                        </div>
                        {mastertable.description && <p className="mt-2 text-gray-600 dark:text-gray-400">{mastertable.description}</p>}
                    </div>

                    {can('edit mastertables') && (
                        <Button variant="outline" onClick={handleEdit}>
                            <Edit className="mr-2 h-4 w-4" />
                            Editar tabla
                        </Button>
                    )}
                </div>

                {/* Items Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Elementos</CardTitle>
                            <CardDescription>Gestiona los elementos de esta tabla maestra</CardDescription>
                        </div>
                        {can('edit mastertables') && !addingItem && (
                            <Button size="sm" onClick={() => setAddingItem(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Agregar elemento
                            </Button>
                        )}
                    </CardHeader>
                    <CardContent>
                        {/* Add New Item Form */}
                        {addingItem && (
                            <form
                                onSubmit={handleAddItem}
                                className="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50"
                            >
                                <div className="flex items-end gap-3">
                                    <div className="flex-1 space-y-2">
                                        <Label htmlFor="new-item-name">Nombre del elemento</Label>
                                        <Input
                                            id="new-item-name"
                                            value={newItemData.name}
                                            onChange={(e) => setNewItemData('name', e.target.value)}
                                            placeholder="Ej: Lentes bifocales"
                                            className={newItemErrors.name ? 'border-red-500' : ''}
                                        />
                                        {newItemErrors.name && <p className="text-sm text-red-500">{newItemErrors.name}</p>}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button type="submit" size="sm" disabled={processingNewItem}>
                                            <Save className="mr-2 h-4 w-4" />
                                            Guardar
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                setAddingItem(false);
                                                resetNewItem();
                                            }}
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </form>
                        )}

                        {/* Items List */}
                        {mastertable.items.length === 0 && !addingItem ? (
                            <div className="py-12 text-center text-gray-500 dark:text-gray-400">
                                <p>No hay elementos en esta tabla maestra</p>
                                {can('edit mastertables') && (
                                    <Button className="mt-4" size="sm" onClick={() => setAddingItem(true)}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Agregar primer elemento
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {mastertable.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                    >
                                        {editingItemId === item.id ? (
                                            <div className="flex flex-1 items-center gap-3">
                                                <Input
                                                    value={editItemData.name}
                                                    onChange={(e) => setEditItemData('name', e.target.value)}
                                                    className={editItemErrors.name ? 'border-red-500' : ''}
                                                />
                                                <div className="flex gap-2">
                                                    <Button size="sm" onClick={() => handleUpdateItem(item.id)} disabled={processingEditItem}>
                                                        <Save className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            setEditingItemId(null);
                                                        }}
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <span className="text-gray-900 dark:text-gray-100">{item.name}</span>
                                                {can('edit mastertables') && (
                                                    <div className="flex gap-2">
                                                        <Button size="sm" variant="ghost" onClick={() => handleEditItem(item)}>
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button size="sm" variant="ghost" onClick={() => handleDeleteItem(item.id)}>
                                                            <Trash2 className="h-4 w-4 text-red-500" />
                                                        </Button>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
