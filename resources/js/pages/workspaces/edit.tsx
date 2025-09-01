import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, Save, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { destroy, index, show, update } from '@/actions/App/Http/Controllers/WorkspaceController';

interface Workspace {
  id: number;
  name: string;
  description: string | null;
  is_owner: boolean;
  members_count: number;
  created_at: string;
}

interface Props {
  workspace: Workspace;
}

export default function EditWorkspace({ workspace }: Props) {
  const { data, setData, patch, processing, errors } = useForm({
    name: workspace.name,
    description: workspace.description || '',
  });

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();
    patch(update(workspace.id.toString()).url);
  };

  const handleDelete = () => {
    if (confirm('Are you sure you want to delete this workspace? This action cannot be undone and will remove all associated data.')) {
      window.location.href = destroy(workspace.id.toString()).url;
    }
  };

  return (
    <AppLayout>
      <Head title={`Edit ${workspace.name}`} />
      
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <Button variant="ghost" asChild className="mb-4">
            <Link href={index().url}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Workspaces
            </Link>
          </Button>
          
          <div className="flex items-center gap-3">
            <Building2 className="h-8 w-8 text-gray-600 dark:text-gray-400" />
            <div>
              <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                Edit Workspace
              </h1>
              <p className="mt-1 text-gray-600 dark:text-gray-400">
                Update your workspace settings and information.
              </p>
            </div>
          </div>
        </div>

        <div className="grid gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Workspace Information</CardTitle>
              <CardDescription>
                Update the basic information for your workspace.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="name">Workspace Name</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Enter workspace name"
                    required
                    className={errors.name ? 'border-red-500' : ''}
                  />
                  {errors.name && (
                    <p className="text-sm text-red-600 dark:text-red-400">
                      {errors.name}
                    </p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Description (Optional)</Label>
                  <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Describe what this workspace is for..."
                    rows={3}
                    className={errors.description ? 'border-red-500' : ''}
                  />
                  {errors.description && (
                    <p className="text-sm text-red-600 dark:text-red-400">
                      {errors.description}
                    </p>
                  )}
                  <p className="text-sm text-gray-600 dark:text-gray-400">
                    Help your team understand the purpose of this workspace.
                  </p>
                </div>

                <div className="flex items-center gap-3 pt-6">
                  <Button type="submit" disabled={processing}>
                    <Save className="h-4 w-4 mr-2" />
                    {processing ? 'Saving...' : 'Save Changes'}
                  </Button>
                  <Button variant="outline" asChild>
                    <Link href={show(workspace.id.toString()).url}>
                      Cancel
                    </Link>
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>

          {workspace.is_owner && (
            <Card className="border-red-200 dark:border-red-800">
              <CardHeader>
                <CardTitle className="text-red-900 dark:text-red-100">
                  Danger Zone
                </CardTitle>
                <CardDescription>
                  Irreversible and destructive actions.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between p-4 border border-red-200 dark:border-red-800 rounded-lg bg-red-50 dark:bg-red-950">
                  <div>
                    <h3 className="font-semibold text-red-900 dark:text-red-100">
                      Delete this workspace
                    </h3>
                    <p className="text-sm text-red-700 dark:text-red-300 mt-1">
                      Once deleted, this workspace and all its data will be permanently removed.
                    </p>
                  </div>
                  <Button 
                    variant="destructive" 
                    onClick={handleDelete}
                    className="shrink-0"
                  >
                    <Trash2 className="h-4 w-4 mr-2" />
                    Delete Workspace
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
