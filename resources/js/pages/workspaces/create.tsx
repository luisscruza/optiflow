import { Head } from '@inertiajs/react';
import { Form } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/WorkspaceController';
import { index } from '@/actions/App/Http/Controllers/WorkspaceController';

export default function WorkspacesCreate() {
    return (
        <AppLayout>
            <Head title="Create Workspace" />
            
            <div className="max-w-2xl mx-auto space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={index()}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Back to Workspaces
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold">Create Workspace</h1>
                        <p className="text-muted-foreground">
                            Set up a new workspace for your team.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Workspace Details</CardTitle>
                        <CardDescription>
                            Provide basic information about your new workspace.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form action={store()} method="post" className="space-y-6">
                            {({ errors, processing }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Workspace Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            placeholder="Enter workspace name"
                                            required
                                            className={errors.name ? 'border-destructive' : ''}
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">{errors.name}</p>
                                        )}
                                        <p className="text-sm text-muted-foreground">
                                            This will be used to identify your workspace.
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="description">Description (Optional)</Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            placeholder="Describe what this workspace is for..."
                                            rows={3}
                                            className={errors.description ? 'border-destructive' : ''}
                                        />
                                        {errors.description && (
                                            <p className="text-sm text-destructive">{errors.description}</p>
                                        )}
                                        <p className="text-sm text-muted-foreground">
                                            Help others understand the purpose of this workspace.
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-4 pt-4">
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Creating...' : 'Create Workspace'}
                                        </Button>
                                        <Link href={index()}>
                                            <Button variant="outline" type="button">
                                                Cancel
                                            </Button>
                                        </Link>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
