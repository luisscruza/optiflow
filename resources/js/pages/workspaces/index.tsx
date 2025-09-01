import { Building2, Crown, MoreHorizontal, Plus, Settings, Users } from 'lucide-react';
import { useState } from 'react';

import AppLayout from '@/layouts/app-layout';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { create, destroy, edit, index, show } from '@/actions/App/Http/Controllers/WorkspaceController';
import { update as switchWorkspace } from '@/actions/App/Http/Controllers/WorkspaceContextController';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Espacios de Trabajo',
    href: index().url,
  },
];

interface Workspace {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  is_owner: boolean;
  members_count: number;
  created_at: string;
}

interface Props {
  workspaces: Workspace[];
  current_workspace: Workspace | null;
}

export default function WorkspacesIndex({ workspaces, current_workspace }: Props) {
  const [deletingWorkspace, setDeletingWorkspace] = useState<string | null>(null);

  const handleSwitchWorkspace = (slug: string) => {
    router.patch(switchWorkspace(slug.toString()).url);
  };

  const handleDeleteWorkspace = (slug: string) => {
    if (confirm('Are you sure you want to delete this workspace? This action cannot be undone.')) {
      setDeletingWorkspace(slug);
      router.delete(destroy(slug.toString()).url, {
        onFinish: () => setDeletingWorkspace(null),
      });
    }
  };

  return (
    <AppLayout>
      <Head title="Workspaces" />
      
      <div className="max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Workspaces
            </h1>
            <p className="mt-2 text-gray-600 dark:text-gray-400">
              Manage your workspaces and switch between different contexts.
            </p>
          </div>
          <Button asChild>
            <Link href={create().url}>
              <Plus className="h-4 w-4 mr-2" />
              Create Workspace
            </Link>
          </Button>
        </div>

        {current_workspace && (
          <div className="mb-8">
            <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950">
              <CardHeader>
                <div className="flex items-center gap-2">
                  <Crown className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                  <CardTitle className="text-blue-900 dark:text-blue-100">
                    Current Workspace
                  </CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="font-semibold text-blue-900 dark:text-blue-100">
                      {current_workspace.name}
                    </h3>
                    {current_workspace.description && (
                      <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        {current_workspace.description}
                      </p>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    {current_workspace.is_owner && (
                      <Badge variant="secondary" className="bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Owner
                      </Badge>
                    )}
                    <div className="flex items-center text-sm text-blue-700 dark:text-blue-300">
                      <Users className="h-4 w-4 mr-1" />
                      {current_workspace.members_count} member{current_workspace.members_count !== 1 ? 's' : ''}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        )}

        {workspaces.length === 0 ? (
          <Card>
            <CardContent className="flex flex-col items-center justify-center py-12">
              <Building2 className="h-12 w-12 text-gray-400 mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                No workspaces yet
              </h3>
              <p className="text-gray-600 dark:text-gray-400 text-center mb-6 max-w-md">
                Get started by creating your first workspace. Workspaces help you organize your work and collaborate with others.
              </p>
              <Button asChild>
                <Link href={create().url}>
                  <Plus className="h-4 w-4 mr-2" />
                  Create your first workspace
                </Link>
              </Button>
            </CardContent>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {workspaces.map((workspace) => (
              <Card 
                key={workspace.id} 
                className={`transition-shadow hover:shadow-lg ${
                  current_workspace?.id === workspace.id 
                    ? 'ring-2 ring-blue-500 shadow-lg' 
                    : ''
                }`}
              >
                <CardHeader className="pb-3">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <CardTitle className="flex items-center gap-2">
                        <Building2 className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                        {workspace.name}
                        {current_workspace?.id === workspace.id && (
                          <Badge variant="outline" className="ml-auto">
                            Current
                          </Badge>
                        )}
                      </CardTitle>
                      {workspace.description && (
                        <CardDescription className="mt-2">
                          {workspace.description}
                        </CardDescription>
                      )}
                    </div>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                          <Link href={show(workspace.slug.toString()).url}>
                            View
                          </Link>
                        </DropdownMenuItem>
                        {current_workspace?.id !== workspace.id && (
                          <DropdownMenuItem 
                            onClick={() => handleSwitchWorkspace(workspace.slug)}
                          >
                            Switch to this workspace
                          </DropdownMenuItem>
                        )}
                        {workspace.is_owner && (
                          <>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem asChild>
                              <Link href={edit(workspace.id.toString()).url}>
                                <Settings className="h-4 w-4 mr-2" />
                                Settings
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem 
                              onClick={() => handleDeleteWorkspace(workspace.slug)}
                              className="text-red-600 dark:text-red-400"
                              disabled={deletingWorkspace === workspace.slug}
                            >
                              {deletingWorkspace === workspace.slug ? 'Deleting...' : 'Delete Workspace'}
                            </DropdownMenuItem>
                          </>
                        )}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                    <div className="flex items-center gap-4">
                      <div className="flex items-center">
                        <Users className="h-4 w-4 mr-1" />
                        {workspace.members_count} member{workspace.members_count !== 1 ? 's' : ''}
                      </div>
                      {workspace.is_owner && (
                        <Badge variant="secondary">
                          Owner
                        </Badge>
                      )}
                    </div>
                    <div className="text-xs">
                      Created {new Date(workspace.created_at).toLocaleDateString()}
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
