import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import { usePermissions } from '@/hooks/use-permissions';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DataTable, type TableResource } from '@/components/ui/datatable';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { SearchableSelect, type SearchableSelectOption } from '@/components/ui/searchable-select';
import { ServerSearchableSelect, type ServerSearchableSelectOption } from '@/components/ui/server-searchable-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Contact, type Mastertable, type MastertableItem, type ProductRecipe, type Workspace } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Recetario de productos',
        href: '/product-recipes',
    },
];

interface ProductRecipeFormData {
    contact_id: number | null;
    optometrist_id: number | null;
    product_id: number | null;
    indication: string;
}

interface Props {
    productRecipes: TableResource<ProductRecipe>;
    productsMastertable: (Pick<Mastertable, 'id' | 'name' | 'alias' | 'description'> & { items: MastertableItem[] }) | null;
    contactSearchResults?: Contact[];
    optometristSearchResults?: Contact[];
}

export default function ProductRecipesIndex({
    productRecipes,
    productsMastertable,
    contactSearchResults = [],
    optometristSearchResults = [],
}: Props) {
    const { can } = usePermissions();
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null; available: Workspace[] } };

    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedContact, setSelectedContact] = useState<Contact | null>(null);
    const [selectedOptometrist, setSelectedOptometrist] = useState<Contact | null>(null);
    const [contactSearchQuery, setContactSearchQuery] = useState('');
    const [optometristSearchQuery, setOptometristSearchQuery] = useState('');
    const [isSearchingContacts, setIsSearchingContacts] = useState(false);
    const [isSearchingOptometrists, setIsSearchingOptometrists] = useState(false);
    const contactSearchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const optometristSearchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<ProductRecipeFormData>({
        contact_id: null,
        optometrist_id: null,
        product_id: null,
        indication: '',
    });

    useEffect(() => {
        return () => {
            if (contactSearchTimeoutRef.current) {
                clearTimeout(contactSearchTimeoutRef.current);
            }

            if (optometristSearchTimeoutRef.current) {
                clearTimeout(optometristSearchTimeoutRef.current);
            }
        };
    }, []);

    const resetModal = () => {
        reset();
        clearErrors();
        setSelectedContact(null);
        setSelectedOptometrist(null);
        setContactSearchQuery('');
        setOptometristSearchQuery('');
    };

    const formatContactOptionLabel = (contact: Contact): string => {
        return contact.phone_primary ? `${contact.name} (${contact.phone_primary})` : contact.name;
    };

    const contactOptions: ServerSearchableSelectOption[] = (contactSearchQuery.length >= 2 ? contactSearchResults : []).map((contact) => ({
        value: contact.id.toString(),
        label: formatContactOptionLabel(contact),
    }));

    const optometristOptions: ServerSearchableSelectOption[] = (optometristSearchQuery.length >= 2 ? optometristSearchResults : []).map(
        (contact) => ({
            value: contact.id.toString(),
            label: formatContactOptionLabel(contact),
        }),
    );

    const productOptions: SearchableSelectOption[] = useMemo(
        () =>
            (productsMastertable?.items ?? []).map((item) => ({
                value: item.id.toString(),
                label: item.name,
            })),
        [productsMastertable],
    );

    const handleContactSearch = (query: string) => {
        if (contactSearchTimeoutRef.current) {
            clearTimeout(contactSearchTimeoutRef.current);
        }

        const normalizedQuery = query.trim();
        setContactSearchQuery(normalizedQuery);

        if (normalizedQuery.length < 2) {
            setIsSearchingContacts(false);

            return;
        }

        contactSearchTimeoutRef.current = setTimeout(() => {
            setIsSearchingContacts(true);
            router.reload({
                only: ['contactSearchResults'],
                data: { contact_search: normalizedQuery },
                onFinish: () => setIsSearchingContacts(false),
            });
        }, 300);
    };

    const handleOptometristSearch = (query: string) => {
        if (optometristSearchTimeoutRef.current) {
            clearTimeout(optometristSearchTimeoutRef.current);
        }

        const normalizedQuery = query.trim();
        setOptometristSearchQuery(normalizedQuery);

        if (normalizedQuery.length < 2) {
            setIsSearchingOptometrists(false);

            return;
        }

        optometristSearchTimeoutRef.current = setTimeout(() => {
            setIsSearchingOptometrists(true);
            router.reload({
                only: ['optometristSearchResults'],
                data: { optometrist_search: normalizedQuery },
                onFinish: () => setIsSearchingOptometrists(false),
            });
        }, 300);
    };

    const handleContactSelect = (value: string) => {
        const selectedId = Number.parseInt(value, 10);
        const contact = contactSearchResults.find((option) => option.id === selectedId) ?? selectedContact;

        setData('contact_id', selectedId);
        setSelectedContact(contact ?? null);
    };

    const handleOptometristSelect = (value: string) => {
        const selectedId = Number.parseInt(value, 10);
        const optometrist = optometristSearchResults.find((option) => option.id === selectedId) ?? selectedOptometrist;

        setData('optometrist_id', selectedId);
        setSelectedOptometrist(optometrist ?? null);
    };

    const handleCreate = () => {
        post('/product-recipes', {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                resetModal();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recetario de productos" />

            <div className="max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Recetario de productos</h1>
                        <p className="text-gray-600 dark:text-gray-400">
                            Crea recetas simples por producto y descarga el PDF para entrega al contacto.
                        </p>
                    </div>

                    {can('create prescriptions') && (
                        <Button onClick={() => setIsCreateModalOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva receta
                        </Button>
                    )}
                </div>


                <DataTable<ProductRecipe>
                    resource={productRecipes}
                    baseUrl="/product-recipes"
                    getRowKey={(recipe) => recipe.id}
                    emptyMessage="No se encontraron recetas de productos"
                    emptyState={
                        can('create prescriptions') ? (
                            <div className="py-8 text-center">
                                <div className="mb-4 text-gray-500 dark:text-gray-400">No se encontraron recetas de productos</div>
                                <Button onClick={() => setIsCreateModalOpen(true)}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Crear primera receta
                                </Button>
                            </div>
                        ) : undefined
                    }
                    handlers={{}}
                />
            </div>

            <Dialog
                open={isCreateModalOpen}
                onOpenChange={(open) => {
                    setIsCreateModalOpen(open);

                    if (!open) {
                        resetModal();
                    }
                }}
            >
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Nueva receta de productos</DialogTitle>
                        <DialogDescription>Selecciona el contacto, evaluador y producto para generar el recetario.</DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-5 py-2">
                        <div className="grid gap-2">
                            <Label>Sucursal</Label>
                            <div className="rounded-md border bg-muted/40 px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                                {workspace?.current?.name ?? 'Sucursal actual'}
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="contact_id">Contacto</Label>
                            <ServerSearchableSelect
                                options={contactOptions}
                                value={data.contact_id?.toString() ?? ''}
                                selectedLabel={selectedContact ? formatContactOptionLabel(selectedContact) : undefined}
                                onValueChange={handleContactSelect}
                                onSearchChange={handleContactSearch}
                                placeholder="Seleccionar contacto..."
                                searchPlaceholder="Buscar contacto..."
                                searchPromptText="Escribe al menos 2 caracteres para buscar contactos."
                                emptyText="No se encontro ningun contacto."
                                loadingText="Buscando contactos..."
                                isLoading={isSearchingContacts}
                            />
                            {errors.contact_id && <p className="text-sm text-red-600">{errors.contact_id}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="optometrist_id">Optometra / Evaluador</Label>
                            <ServerSearchableSelect
                                options={optometristOptions}
                                value={data.optometrist_id?.toString() ?? ''}
                                selectedLabel={selectedOptometrist ? formatContactOptionLabel(selectedOptometrist) : undefined}
                                onValueChange={handleOptometristSelect}
                                onSearchChange={handleOptometristSearch}
                                placeholder="Seleccionar evaluador..."
                                searchPlaceholder="Buscar evaluador..."
                                searchPromptText="Escribe al menos 2 caracteres para buscar evaluadores."
                                emptyText="No se encontro ningun evaluador."
                                loadingText="Buscando evaluadores..."
                                isLoading={isSearchingOptometrists}
                            />
                            {errors.optometrist_id && <p className="text-sm text-red-600">{errors.optometrist_id}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="product_id">Producto</Label>
                            <SearchableSelect
                                options={productOptions}
                                value={data.product_id?.toString() ?? ''}
                                onValueChange={(value) => setData('product_id', Number.parseInt(value, 10))}
                                placeholder="Seleccionar producto..."
                                searchPlaceholder="Buscar producto..."
                                emptyText="No hay productos configurados."
                                footerAction={
                                    <Link
                                        href={productsMastertable ? `/mastertables/${productsMastertable.id}` : '/mastertables'}
                                        className="block text-center text-sm font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        Ir a configurar productos
                                    </Link>
                                }
                            />
                            {errors.product_id && <p className="text-sm text-red-600">{errors.product_id}</p>}
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="indication">Indicacion manual</Label>
                            <Textarea
                                id="indication"
                                value={data.indication}
                                onChange={(event) => setData('indication', event.target.value)}
                                rows={6}
                                placeholder="Escribe una indicacion opcional para el producto..."
                            />
                            {errors.indication && <p className="text-sm text-red-600">{errors.indication}</p>}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setIsCreateModalOpen(false)} disabled={processing}>
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            onClick={handleCreate}
                            disabled={processing || !data.contact_id || !data.optometrist_id || !data.product_id}
                        >
                            {processing ? 'Guardando...' : 'Guardar receta'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
