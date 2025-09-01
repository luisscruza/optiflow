import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Building2, Calendar, Crown, Edit, Settings, Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { edit, index } from '@/routes/workspaces';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Workspace {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  is_owner: boolean;
  members_count: number;
  created_at: string;
  updated_at: string;
}

interface Props {
  workspace: Workspace;
  members: User[];
  can_edit: boolean;
}

export default function ShowWorkspace({ workspace, members, can_edit }: Props) {
  return (
    <AppLayout>
      <Head title={workspace.name} />
      
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <Button variant="ghost" asChild className="mb-4">
            <Link href={index().url}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Workspaces
            </Link>
          </Button>
          
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-3">
              <Building2 className="h-8 w-8 text-gray-600 dark:text-gray-400" />
              <div>
                <div className="flex items-center gap-2">
                  <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                    {workspace.name}
                  </h1>
                  {workspace.is_owner && (
                    <Badge variant="secondary">
                      <Crown className="h-3 w-3 mr-1" />
                      Owner
                    </Badge>
                  )}
                </div>
                {workspace.description && (
                  <p className="mt-2 text-gray-600 dark:text-gray-400">
                    {workspace.description}
                  </p>
                )}
              </div>
            </div>
            
            {can_edit && (
              <Button asChild>
                <Link href={edit(workspace.slug).url}>
                  <Settings className="h-4 w-4 mr-2" />
                  Settings
                </Link>
              </Button>
            )}
          </div>
        </div>

        <div className="grid gap-6">
          {/* Workspace Overview */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Building2 className="h-5 w-5" />
                Workspace Overview
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <Users className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                      Members
                    </p>
                    <p className="text-2xl font-bold text-gray-900 dark:text-white">
                      {workspace.members_count}
                    </p>
                  </div>
                </div>
                
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <Calendar className="h-5 w-5 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                      Created
                    </p>
                    <p className="text-lg font-semibold text-gray-900 dark:text-white">
                      {new Date(workspace.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
                
                <div className="flex items-center gap-3">
                  <div className="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <Edit className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                  </div>
                  <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                      Last Updated
                    </p>
                    <p className="text-lg font-semibold text-gray-900 dark:text-white">
                      {new Date(workspace.updated_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Members */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                Workspace Members
              </CardTitle>
              <CardDescription>
                People who have access to this workspace.
              </CardDescription>
            </CardHeader>
            <CardContent>
              {members?.length === 0 ? (
                <div className="text-center py-8">
                  <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    No members yet
                  </h3>
                  <p className="text-gray-600 dark:text-gray-400">
                    Invite people to collaborate in this workspace.
                  </p>
                </div>
              ) : (
                <div className="space-y-4">
                  {members?.map((member) => (
                    <div
                      key={member.id}
                      className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                    >
                      <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                          <span className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {member.name.charAt(0).toUpperCase()}
                          </span>
                        </div>
                        <div>
                          <p className="font-semibold text-gray-900 dark:text-white">
                            {member.name}
                          </p>
                          <p className="text-sm text-gray-600 dark:text-gray-400">
                            {member.email}
                          </p>
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-2">
                        {member.id === workspace.id && workspace.is_owner && (
                          <Badge variant="secondary">
                            <Crown className="h-3 w-3 mr-1" />
                            Owner
                          </Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>
                Common actions you can perform in this workspace.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {can_edit && (
                  <Button variant="outline" asChild className="justify-start">
                    <Link href={edit(workspace.slug).url}>
                      <Settings className="h-4 w-4 mr-2" />
                      Workspace Settings
                    </Link>
                  </Button>
                )}
                
                <Button variant="outline" asChild className="justify-start">
                  <Link href={index().url}>
                    <Building2 className="h-4 w-4 mr-2" />
                    View All Workspaces
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
