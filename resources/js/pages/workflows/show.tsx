import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Building2, CalendarIcon, Edit, Filter, LayoutGrid, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ServerSearchableSelect, type ServerSearchableSelectOption } from '@/components/ui/server-searchable-select';
import { KanbanBoard } from '@/components/workflows/kanban-board';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import {
    type BreadcrumbItem,
    type Contact,
    type CursorPaginatedData,
    type Invoice,
    type Prescription,
    type Workflow,
    type WorkflowJob,
    type WorkflowJobPriority,
} from '@/types';

interface Filters {
    contact_id?: string;
    priority?: WorkflowJobPriority;
    status?: 'pending' | 'completed' | 'canceled';
    due_status?: 'overdue' | 'not_overdue';
    date_from?: string;
    date_to?: string;
}

interface Props {
    workflow: Workflow;
    filters?: Filters;
    showAllWorkspaces?: boolean;
    invoices?: Invoice[];
    selectedContact?: Contact | null;
    contactSearchResults?: Contact[];
    prescriptions?: Prescription[];
    // Stage jobs are passed as flat props like stage_{uuid}_jobs
    [key: `stage_${string}_jobs`]: CursorPaginatedData<WorkflowJob>;
}

export default function WorkflowShow({
    workflow,
    filters = {},
    showAllWorkspaces = false,
    invoices = [],
    selectedContact = null,
    contactSearchResults = [],
    prescriptions = [],
    ...rest
}: Props) {
    const { can } = usePermissions();

    const [isFilterOpen, setIsFilterOpen] = useState(false);
    const [localFilters, setLocalFilters] = useState<Filters>(filters);
    const [selectedFilterContact, setSelectedFilterContact] = useState<Contact | null>(selectedContact);
    const [contactSearchQuery, setContactSearchQuery] = useState('');
    const [isSearchingContacts, setIsSearchingContacts] = useState(false);
    const contactSearchTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (selectedContact && filters.contact_id && String(selectedContact.id) === filters.contact_id) {
            setSelectedFilterContact(selectedContact);
        }
    }, [selectedContact, filters.contact_id]);

    useEffect(() => {
        return () => {
            if (contactSearchTimeoutRef.current) {
                clearTimeout(contactSearchTimeoutRef.current);
            }
        };
    }, []);

    // Extract stage jobs from the flat props
    const stageJobs: Record<string, CursorPaginatedData<WorkflowJob>> = {};
    for (const [key, value] of Object.entries(rest)) {
        if (key.startsWith('stage_') && key.endsWith('_jobs')) {
            // Convert prop name back to stage ID: stage_{uuid}_jobs -> uuid (with dashes restored)
            const stageId = key.slice(6, -5).replace(/_/g, '-');
            stageJobs[stageId] = value as CursorPaginatedData<WorkflowJob>;
        }
    }

    const activeFilterCount = Object.values(filters).filter(Boolean).length;

    const buildParams = (filterOverrides: Partial<Filters> = {}, allWorkspaces?: boolean) => {
        const currentFilters = { ...filters, ...filterOverrides };
        const params: Record<string, string> = {};
        if (currentFilters.contact_id) params.filter_contact = currentFilters.contact_id;
        if (currentFilters.priority) params.filter_priority = currentFilters.priority;
        if (currentFilters.status) params.filter_status = currentFilters.status;
        if (currentFilters.due_status) params.filter_due_status = currentFilters.due_status;
        if (currentFilters.date_from) params.filter_date_from = currentFilters.date_from;
        if (currentFilters.date_to) params.filter_date_to = currentFilters.date_to;
        if (allWorkspaces ?? showAllWorkspaces) params.all_workspaces = '1';
        return params;
    };

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (localFilters.contact_id) params.filter_contact = localFilters.contact_id;
        if (localFilters.priority) params.filter_priority = localFilters.priority;
        if (localFilters.status) params.filter_status = localFilters.status;
        if (localFilters.due_status) params.filter_due_status = localFilters.due_status;
        if (localFilters.date_from) params.filter_date_from = localFilters.date_from;
        if (localFilters.date_to) params.filter_date_to = localFilters.date_to;
        if (showAllWorkspaces) params.all_workspaces = '1';

        router.get(`/workflows/${workflow.id}`, params, {
            preserveState: true,
            preserveScroll: true,
        });
        setIsFilterOpen(false);
    };

    const clearFilters = () => {
        setLocalFilters({});
        const params: Record<string, string> = {};
        if (showAllWorkspaces) params.all_workspaces = '1';
        router.get(`/workflows/${workflow.id}`, params, {
            preserveState: true,
            preserveScroll: true,
        });
        setIsFilterOpen(false);
    };

    const toggleAllWorkspaces = () => {
        const params = buildParams({}, !showAllWorkspaces);
        if (!showAllWorkspaces) {
            params.all_workspaces = '1';
        } else {
            delete params.all_workspaces;
        }
        router.get(`/workflows/${workflow.id}`, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const removeFilter = (key: keyof Filters) => {
        const newFilters = { ...filters };
        delete newFilters[key];

        const params: Record<string, string> = {};
        if (newFilters.contact_id) params.filter_contact = newFilters.contact_id;
        if (newFilters.priority) params.filter_priority = newFilters.priority;
        if (newFilters.status) params.filter_status = newFilters.status;
        if (newFilters.due_status) params.filter_due_status = newFilters.due_status;
        if (newFilters.date_from) params.filter_date_from = newFilters.date_from;
        if (newFilters.date_to) params.filter_date_to = newFilters.date_to;
        if (showAllWorkspaces) params.all_workspaces = '1';

        router.get(`/workflows/${workflow.id}`, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const formatDate = (dateStr: string) => {
        return new Date(dateStr).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    const getContactName = (contactId: string) => {
        return selectedFilterContact && String(selectedFilterContact.id) === contactId ? selectedFilterContact.name : contactId;
    };

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

    const formatContactOptionLabel = (contact: Contact): string => {
        return contact.identification_number ? `${contact.name} (${contact.identification_number})` : contact.name;
    };

    // Build searchable options for contacts
    const contactOptions: ServerSearchableSelectOption[] = [
        { value: 'all', label: 'Todos los clientes' },
        ...(contactSearchQuery.length >= 2 ? contactSearchResults : []).map((contact) => ({
            value: String(contact.id),
            label: formatContactOptionLabel(contact),
        })),
    ];

    const priorityLabels: Record<WorkflowJobPriority, string> = {
        low: 'Baja',
        medium: 'Media',
        high: 'Alta',
        urgent: 'Urgente',
    };

    const statusLabels: Record<string, string> = {
        pending: 'Pendiente',
        completed: 'Completada',
        canceled: 'Cancelada',
    };

    const dueStatusLabels: Record<string, string> = {
        overdue: 'Vencido',
        not_overdue: 'No vencido',
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Procesos',
            href: '/workflows',
        },
        {
            title: workflow.name,
            href: `/workflows/${workflow.id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={workflow.name} />

            <div className="flex h-[calc(100vh-8rem)] flex-col px-4 py-4 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/workflows">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Volver
                            </Button>
                        </Link>
                        <div className="flex items-center gap-2">
                            <LayoutGrid className="h-5 w-5" />
                            <h1 className="text-2xl font-bold">{workflow.name}</h1>
                            <Badge variant={workflow.is_active ? 'default' : 'secondary'}>{workflow.is_active ? 'Activo' : 'Inactivo'}</Badge>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* All Workspaces Toggle */}
                        {can('view all locations') && (
                            <Button
                                variant={showAllWorkspaces ? 'default' : 'outline'}
                                size="sm"
                                onClick={toggleAllWorkspaces}
                                title={showAllWorkspaces ? 'Mostrando todas las sucursales' : 'Mostrar todas las sucursales'}
                            >
                                <Building2 className="mr-2 h-4 w-4" />
                                {showAllWorkspaces ? 'Todas las sucursales' : 'Mi sucursal'}
                            </Button>
                        )}

                        {/* Filter Button */}
                        <Popover open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                            <PopoverTrigger asChild>
                                <Button variant="outline" size="sm" className="relative">
                                    <Filter className="mr-2 h-4 w-4" />
                                    Filtrar
                                    {activeFilterCount > 0 && (
                                        <Badge className="ml-2 h-5 w-5 rounded-full p-0 text-xs" variant="default">
                                            {activeFilterCount}
                                        </Badge>
                                    )}
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-80" align="end">
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <h4 className="font-medium">Filtros</h4>
                                        {activeFilterCount > 0 && (
                                            <Button variant="ghost" size="sm" onClick={clearFilters}>
                                                Limpiar
                                            </Button>
                                        )}
                                    </div>

                                    {/* Client Filter */}
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Cliente</label>
                                        <ServerSearchableSelect
                                            options={contactOptions}
                                            value={localFilters.contact_id || 'all'}
                                            minSearchLength={0}
                                            selectedLabel={selectedFilterContact ? formatContactOptionLabel(selectedFilterContact) : undefined}
                                            onValueChange={(v) => {
                                                if (v === 'all') {
                                                    setLocalFilters({ ...localFilters, contact_id: undefined });
                                                    setSelectedFilterContact(null);

                                                    return;
                                                }

                                                const selected = contactSearchResults.find((contact) => String(contact.id) === v);
                                                setLocalFilters({ ...localFilters, contact_id: v });
                                                setSelectedFilterContact(selected || null);
                                            }}
                                            onSearchChange={handleContactSearch}
                                            placeholder="Todos los clientes"
                                            searchPlaceholder="Buscar cliente..."
                                            searchPromptText="Escribe al menos 2 caracteres para buscar clientes."
                                            emptyText="No se encontraron clientes"
                                            loadingText="Buscando clientes..."
                                            isLoading={isSearchingContacts}
                                        />
                                    </div>

                                    {/* Priority Filter */}
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Prioridad</label>
                                        <Select
                                            value={localFilters.priority || 'all'}
                                            onValueChange={(v) =>
                                                setLocalFilters({ ...localFilters, priority: v === 'all' ? undefined : (v as WorkflowJobPriority) })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todas las prioridades" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todas las prioridades</SelectItem>
                                                <SelectItem value="low">Baja</SelectItem>
                                                <SelectItem value="medium">Media</SelectItem>
                                                <SelectItem value="high">Alta</SelectItem>
                                                <SelectItem value="urgent">Urgente</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Status Filter */}
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Estado</label>
                                        <Select
                                            value={localFilters.status || 'all'}
                                            onValueChange={(v) =>
                                                setLocalFilters({ ...localFilters, status: v === 'all' ? undefined : (v as Filters['status']) })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos los estados" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos los estados</SelectItem>
                                                <SelectItem value="pending">Pendiente</SelectItem>
                                                <SelectItem value="completed">Completada</SelectItem>
                                                <SelectItem value="canceled">Cancelada</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Due Status Filter */}
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Vencimiento</label>
                                        <Select
                                            value={localFilters.due_status || 'all'}
                                            onValueChange={(v) =>
                                                setLocalFilters({
                                                    ...localFilters,
                                                    due_status: v === 'all' ? undefined : (v as Filters['due_status']),
                                                })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Todos" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Todos</SelectItem>
                                                <SelectItem value="overdue">Vencido</SelectItem>
                                                <SelectItem value="not_overdue">No vencido</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Date Range Filter */}
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Fecha de creación</label>
                                        <div className="grid grid-cols-2 gap-2">
                                            <Popover>
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className={cn(
                                                            'justify-start text-left font-normal',
                                                            !localFilters.date_from && 'text-muted-foreground',
                                                        )}
                                                    >
                                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                                        {localFilters.date_from ? formatDate(localFilters.date_from) : 'Desde'}
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-auto p-0" align="start">
                                                    <Calendar
                                                        mode="single"
                                                        selected={localFilters.date_from ? new Date(localFilters.date_from) : undefined}
                                                        onSelect={(date: Date | undefined) =>
                                                            setLocalFilters({ ...localFilters, date_from: date?.toISOString().split('T')[0] })
                                                        }
                                                        initialFocus
                                                    />
                                                </PopoverContent>
                                            </Popover>
                                            <Popover>
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className={cn(
                                                            'justify-start text-left font-normal',
                                                            !localFilters.date_to && 'text-muted-foreground',
                                                        )}
                                                    >
                                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                                        {localFilters.date_to ? formatDate(localFilters.date_to) : 'Hasta'}
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="w-auto p-0" align="start">
                                                    <Calendar
                                                        mode="single"
                                                        selected={localFilters.date_to ? new Date(localFilters.date_to) : undefined}
                                                        onSelect={(date: Date | undefined) =>
                                                            setLocalFilters({ ...localFilters, date_to: date?.toISOString().split('T')[0] })
                                                        }
                                                        initialFocus
                                                    />
                                                </PopoverContent>
                                            </Popover>
                                        </div>
                                    </div>

                                    <Button onClick={applyFilters} className="w-full">
                                        Aplicar filtros
                                    </Button>
                                </div>
                            </PopoverContent>
                        </Popover>

                        {can('edit workflows') && (
                            <Link href={`/workflows/${workflow.id}/edit`}>
                                <Button variant="outline" size="sm">
                                    <Edit className="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* Active Filters */}
                {activeFilterCount > 0 && (
                    <div className="mb-4 flex flex-wrap items-center gap-2">
                        <span className="text-sm text-muted-foreground">Filtros activos:</span>
                        {filters.contact_id && (
                            <Badge variant="secondary" className="gap-1">
                                Cliente: {getContactName(filters.contact_id)}
                                <button onClick={() => removeFilter('contact_id')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {filters.priority && (
                            <Badge variant="secondary" className="gap-1">
                                Prioridad: {priorityLabels[filters.priority]}
                                <button onClick={() => removeFilter('priority')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {filters.status && (
                            <Badge variant="secondary" className="gap-1">
                                Estado: {statusLabels[filters.status]}
                                <button onClick={() => removeFilter('status')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {filters.due_status && (
                            <Badge variant="secondary" className="gap-1">
                                Vencimiento: {dueStatusLabels[filters.due_status]}
                                <button onClick={() => removeFilter('due_status')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {filters.date_from && (
                            <Badge variant="secondary" className="gap-1">
                                Desde: {formatDate(filters.date_from)}
                                <button onClick={() => removeFilter('date_from')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        {filters.date_to && (
                            <Badge variant="secondary" className="gap-1">
                                Hasta: {formatDate(filters.date_to)}
                                <button onClick={() => removeFilter('date_to')} className="ml-1 hover:text-destructive">
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        )}
                        <Button variant="ghost" size="sm" onClick={clearFilters} className="h-6 px-2 text-xs">
                            Limpiar todos
                        </Button>
                    </div>
                )}

                {/* Kanban Board */}
                <div className="flex-1 overflow-hidden">
                    <KanbanBoard
                        workflow={workflow}
                        stageJobs={stageJobs}
                        invoices={invoices}
                        contacts={contactSearchResults}
                        prescriptions={prescriptions}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
