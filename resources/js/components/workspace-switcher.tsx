import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type Workspace } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, Settings } from 'lucide-react';
import { update } from '@/actions/App/Http/Controllers/WorkspaceContextController';
import { create, index } from '@/actions/App/Http/Controllers/WorkspaceController';

export function WorkspaceSwitcher() {
    const { workspace } = usePage().props as { workspace?: { current: Workspace | null; available: Workspace[] } };

    if (!workspace || !workspace.available?.length) {
        return null;
    }

    const { current, available } = workspace;

    const switchWorkspace = (targetWorkspace: Workspace) => {
        if (current?.id === targetWorkspace.id) {
            return;
        }

        router.patch(
            update({ workspace: targetWorkspace.slug }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
            }
        );
    };

    const createWorkspace = () => {
        router.visit(create());
    };

    const manageWorkspaces = () => {
        router.visit(index());
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <span className="text-xs font-semibold">
                                    {current?.name?.charAt(0).toUpperCase() || 'W'}
                                </span>
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">
                                    {current?.name || 'Select Workspace'}
                                </span>
                                <span className="truncate text-xs">
                                    {available.length} workspace{available.length !== 1 ? 's' : ''}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                        align="start"
                        side="bottom"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Sucursales
                        </DropdownMenuLabel>
                        {available.map((workspace: Workspace) => (
                            <DropdownMenuItem
                                key={workspace.id}
                                onClick={() => switchWorkspace(workspace)}
                                className="gap-2 p-2"
                            >
                                <div className="flex size-6 items-center justify-center rounded-sm border">
                                    <span className="text-xs font-semibold">
                                        {workspace.name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div className="flex-1">
                                    <div className="font-medium">{workspace.name}</div>
                                    {workspace.description && (
                                        <div className="text-xs text-muted-foreground">
                                            {workspace.description}
                                        </div>
                                    )}
                                </div>
                                {current?.id === workspace.id && (
                                    <Check className="size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={createWorkspace}
                            className="gap-2 p-2"
                        >
                            <div className="flex size-6 items-center justify-center rounded-md border border-dashed">
                                <Plus className="size-4" />
                            </div>
                            Create workspace
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={manageWorkspaces}
                            className="gap-2 p-2"
                        >
                            <div className="flex size-6 items-center justify-center rounded-md border">
                                <Settings className="size-4" />
                            </div>
                            Manage workspaces
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
