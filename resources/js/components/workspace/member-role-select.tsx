import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Settings } from 'lucide-react';
import { useState } from 'react';

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    joined_at: string;
}

interface Props {
    member: Member;
    roles: Record<string, string>;
}

export function MemberRoleSelect({ member, roles }: Props) {
    const [showDialog, setShowDialog] = useState(false);
    const [selectedRole, setSelectedRole] = useState(member.role);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedRole === member.role) return;

        setProcessing(true);
        router.patch(`/workspace/members/${member.id}/role`, {
            role: selectedRole,
        }, {
            onSuccess: () => {
                setShowDialog(false);
                setErrors({});
            },
            onError: (errors) => {
                setErrors(errors);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    return (
        <Dialog open={showDialog} onOpenChange={setShowDialog}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <Settings className="h-4 w-4" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Cambiar rol de {member.name}</DialogTitle>
                    <DialogDescription>
                        Selecciona el nuevo rol para este miembro de la sucursal.
                    </DialogDescription>
                </DialogHeader>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Select value={selectedRole} onValueChange={setSelectedRole}>
                            <SelectTrigger>
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
                        {errors.role && (
                            <p className="text-sm text-red-600 mt-1">{errors.role}</p>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <Button 
                            type="button" 
                            variant="outline" 
                            onClick={() => setShowDialog(false)}
                            className="flex-1"
                        >
                            Cancelar
                        </Button>
                        <Button 
                            type="submit" 
                            disabled={processing || selectedRole === member.role}
                            className="flex-1"
                        >
                            {processing ? 'Actualizando...' : 'Actualizar rol'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}