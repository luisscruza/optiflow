import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { Plus, Search, User, UserPlus, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Props {
    roles: Record<string, string>;
    availableWorkspaces: Array<{ id: number; name: string }>;
    onSuccess?: () => void;
}

interface WorkspaceAssignment {
    workspace_id: number;
    workspace_name: string;
    role: string;
}

interface ExistingUser {
    id: number;
    name: string;
    email: string;
}

export function InviteUserForm({ roles, availableWorkspaces, onSuccess }: Props) {
    const [email, setEmail] = useState('');
    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [workspaceAssignments, setWorkspaceAssignments] = useState<WorkspaceAssignment[]>([]);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [existingUser, setExistingUser] = useState<ExistingUser | null>(null);
    const [searchingUser, setSearchingUser] = useState(false);
    const [userSearchResults, setUserSearchResults] = useState<ExistingUser[]>([]);
    const [mode, setMode] = useState<'search' | 'create'>('search');

    // Search for users when email changes
    useEffect(() => {
        if (email && email.includes('@') && mode === 'search') {
            setSearchingUser(true);

            // Simulate API call - in real app, you'd make a request to search users
            const searchUsers = async () => {
                try {
                    // You would replace this with an actual API call
                    const response = await fetch(`/api/users/search?email=${encodeURIComponent(email)}`);
                    if (response.ok) {
                        const users = await response.json();
                        setUserSearchResults(users);

                        // If exact match found, auto-select
                        const exactMatch = users.find((user: ExistingUser) => user.email.toLowerCase() === email.toLowerCase());
                        if (exactMatch) {
                            setExistingUser(exactMatch);
                            setName(exactMatch.name);
                        } else {
                            setExistingUser(null);
                        }
                    }
                } catch (error) {
                    console.error('Error searching users:', error);
                } finally {
                    setSearchingUser(false);
                }
            };

            const timeoutId = setTimeout(searchUsers, 300);
            return () => clearTimeout(timeoutId);
        } else {
            setUserSearchResults([]);
            setExistingUser(null);
            if (mode === 'search') {
                setName('');
            }
        }
    }, [email, mode]);

    const addWorkspaceAssignment = () => {
        if (availableWorkspaces.length > 0) {
            const firstWorkspace = availableWorkspaces[0];
            const firstRole = Object.keys(roles)[0];

            setWorkspaceAssignments([
                ...workspaceAssignments,
                {
                    workspace_id: firstWorkspace.id,
                    workspace_name: firstWorkspace.name,
                    role: firstRole,
                },
            ]);
        }
    };

    const removeWorkspaceAssignment = (index: number) => {
        setWorkspaceAssignments(workspaceAssignments.filter((_, i) => i !== index));
    };

    const updateWorkspaceAssignment = (index: number, field: 'workspace_id' | 'role', value: string) => {
        const updated = [...workspaceAssignments];
        if (field === 'workspace_id') {
            const workspace = availableWorkspaces.find((w) => w.id === Number(value));
            updated[index] = {
                ...updated[index],
                workspace_id: Number(value),
                workspace_name: workspace?.name || '',
            };
        } else {
            updated[index] = { ...updated[index], role: value };
        }
        setWorkspaceAssignments(updated);
    };

    const selectExistingUser = (user: ExistingUser) => {
        setExistingUser(user);
        setEmail(user.email);
        setName(user.name);
        setUserSearchResults([]);
    };

    const switchToCreateMode = () => {
        setMode('create');
        setExistingUser(null);
        setName('');
        setPassword('');
        setPasswordConfirmation('');
    };

    const switchToSearchMode = () => {
        setMode('search');
        setPassword('');
        setPasswordConfirmation('');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!email || workspaceAssignments.length === 0) return;

        // If creating new user, validate required fields
        if (!existingUser && (!name || !password || !passwordConfirmation)) return;

        setProcessing(true);

        const data: any = {
            email,
            workspace_assignments: workspaceAssignments.map((assignment) => ({
                workspace_id: assignment.workspace_id,
                role: assignment.role,
            })),
        };

        // Add name and password only if creating new user
        if (!existingUser) {
            data.name = name;
            data.password = password;
            data.password_confirmation = passwordConfirmation;
        }

        router.post('/workspace/invitations', data, {
            onSuccess: () => {
                setEmail('');
                setName('');
                setPassword('');
                setPasswordConfirmation('');
                setWorkspaceAssignments([]);
                setExistingUser(null);
                setErrors({});
                onSuccess?.();
            },
            onError: (errors) => {
                setErrors(errors);
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    const isFormValid =
        email && workspaceAssignments.length > 0 && (existingUser || (name && password && passwordConfirmation && password === passwordConfirmation));

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* User Selection */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <User className="h-5 w-5" />
                        Seleccionar Usuario
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Mode Toggle */}
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant={mode === 'search' ? 'default' : 'outline'}
                            size="sm"
                            onClick={switchToSearchMode}
                            className="gap-2"
                        >
                            <Search className="h-4 w-4" />
                            Buscar existente
                        </Button>
                        <Button
                            type="button"
                            variant={mode === 'create' ? 'default' : 'outline'}
                            size="sm"
                            onClick={switchToCreateMode}
                            className="gap-2"
                        >
                            <UserPlus className="h-4 w-4" />
                            Crear nuevo
                        </Button>
                    </div>

                    {/* Email Field */}
                    <div>
                        <Label htmlFor="email">Correo electrónico *</Label>
                        <div className="relative">
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="usuario@ejemplo.com"
                                className="mt-1"
                                required
                            />
                            {searchingUser && (
                                <div className="absolute top-3 right-2">
                                    <div className="h-4 w-4 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                </div>
                            )}
                        </div>
                        {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                    </div>

                    {/* User Search Results */}
                    {mode === 'search' && userSearchResults.length > 0 && !existingUser && (
                        <div className="space-y-2 rounded-md border p-2">
                            <p className="text-sm text-gray-600">Usuarios encontrados:</p>
                            {userSearchResults.map((user) => (
                                <div
                                    key={user.id}
                                    className="flex cursor-pointer items-center justify-between rounded p-2 hover:bg-gray-50"
                                    onClick={() => selectExistingUser(user)}
                                >
                                    <div>
                                        <p className="font-medium">{user.name}</p>
                                        <p className="text-sm text-gray-600">{user.email}</p>
                                    </div>
                                    <Button type="button" size="sm" variant="outline">
                                        Seleccionar
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Selected User Display */}
                    {existingUser && (
                        <div className="rounded-md border border-green-200 bg-green-50 p-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="font-medium text-green-800">Usuario seleccionado:</p>
                                    <p className="text-green-700">{existingUser.name}</p>
                                    <p className="text-sm text-green-600">{existingUser.email}</p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setExistingUser(null);
                                        setEmail('');
                                        setName('');
                                    }}
                                >
                                    Cambiar
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* New User Fields */}
                    {mode === 'create' && !existingUser && (
                        <>
                            <div>
                                <Label htmlFor="name">Nombre completo *</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Ingresa el nombre completo"
                                    className="mt-1"
                                    required={!existingUser}
                                />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="password">Contraseña temporal *</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    placeholder="Mínimo 8 caracteres"
                                    className="mt-1"
                                    minLength={8}
                                    required={!existingUser}
                                />
                                {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                            </div>

                            <div>
                                <Label htmlFor="password_confirmation">Confirmar contraseña *</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    placeholder="Confirma la contraseña"
                                    className="mt-1"
                                    required={!existingUser}
                                />
                                {errors.password_confirmation && <p className="mt-1 text-sm text-red-600">{errors.password_confirmation}</p>}
                                {password && passwordConfirmation && password !== passwordConfirmation && (
                                    <p className="mt-1 text-sm text-red-600">Las contraseñas no coinciden</p>
                                )}
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>

            {/* Workspace Assignments */}
            <Card>
                <CardHeader className="pb-3">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-lg">Asignación de sucursales</CardTitle>
                        <Button type="button" variant="outline" size="sm" onClick={addWorkspaceAssignment} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Agregar sucursal
                        </Button>
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    {workspaceAssignments.length === 0 && (
                        <p className="py-4 text-center text-gray-500">Agregue al menos un workspace para continuar</p>
                    )}

                    {workspaceAssignments.map((assignment, index) => (
                        <div key={index} className="flex items-start gap-3 rounded-lg border p-3">
                            <div className="flex-1 space-y-3">
                                <div>
                                    <Label>Workspace</Label>
                                    <Select
                                        value={assignment.workspace_id.toString()}
                                        onValueChange={(value) => updateWorkspaceAssignment(index, 'workspace_id', value)}
                                    >
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder="Seleccionar workspace" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableWorkspaces.map((workspace) => (
                                                <SelectItem key={workspace.id} value={workspace.id.toString()}>
                                                    {workspace.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label>Rol</Label>
                                    <Select value={assignment.role} onValueChange={(value) => updateWorkspaceAssignment(index, 'role', value)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder="Seleccionar rol" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(roles).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => removeWorkspaceAssignment(index)}
                                className="mt-6 text-red-600 hover:text-red-700"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    ))}

                    {errors.workspace_assignments && <p className="text-sm text-red-600">{errors.workspace_assignments}</p>}
                </CardContent>
            </Card>

            {/* Actions */}
            <div className="flex gap-2 pt-4">
                <Button type="button" variant="outline" onClick={onSuccess} className="flex-1">
                    Cancelar
                </Button>
                <Button type="submit" disabled={processing || !isFormValid} className="flex-1 bg-yellow-600 hover:bg-yellow-700">
                    {processing ? 'Procesando...' : existingUser ? 'Asignar usuario' : 'Crear y asignar'}
                </Button>
            </div>
        </form>
    );
}
