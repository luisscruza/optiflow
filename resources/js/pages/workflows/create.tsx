import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, LayoutGrid } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { WorkflowFieldsEditor } from '@/components/workflows/workflow-fields-editor';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Mastertable } from '@/types';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    mastertables: Mastertable[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Procesos',
        href: '/workflows',
    },
    {
        title: 'Crear flujo',
        href: '/workflows/create',
    },
];

export default function WorkflowCreate({ mastertables }: Props) {
    const [invoiceRequirement, setInvoiceRequirement] = useState<string>('none');
    const [prescriptionRequirement, setPrescriptionRequirement] = useState<string>('none');
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Crear flujo de trabajo" />

            <div className="max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <Link href="/workflows">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Volver
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <LayoutGrid className="h-5 w-5" />
                            Nuevo flujo de trabajo
                        </CardTitle>
                        <CardDescription>
                            Crea un nuevo flujo de trabajo para gestionar tus procesos. Se crearán etapas iniciales automáticamente.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Form action="/workflows" method="post">
                            {({ errors, processing }) => (
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nombre del flujo</Label>
                                        <Input id="name" name="name" placeholder="Ej: Proceso de lentes" required />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="invoice_requirement">Factura</Label>
                                            <input
                                                type="hidden"
                                                name="invoice_requirement"
                                                value={invoiceRequirement === 'none' ? '' : invoiceRequirement}
                                            />
                                            <Select value={invoiceRequirement} onValueChange={setInvoiceRequirement}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="No requiere factura" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">No requiere factura</SelectItem>
                                                    <SelectItem value="optional">Opcional</SelectItem>
                                                    <SelectItem value="required">Requerido</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p className="text-xs text-muted-foreground">
                                                Define si las tareas de este flujo requieren una factura asociada.
                                            </p>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="prescription_requirement">Receta</Label>
                                            <input
                                                type="hidden"
                                                name="prescription_requirement"
                                                value={prescriptionRequirement === 'none' ? '' : prescriptionRequirement}
                                            />
                                            <Select value={prescriptionRequirement} onValueChange={setPrescriptionRequirement}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="No requiere receta" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="none">No requiere receta</SelectItem>
                                                    <SelectItem value="optional">Opcional</SelectItem>
                                                    <SelectItem value="required">Requerido</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p className="text-xs text-muted-foreground">
                                                Define si las tareas de este flujo requieren una receta asociada.
                                            </p>
                                        </div>
                                    </div>

                                    <WorkflowFieldsEditor fields={[]} mastertables={mastertables} onChange={() => {}} />

                                    <div className="flex justify-end gap-4">
                                        <Link href="/workflows">
                                            <Button type="button" variant="outline">
                                                Cancelar
                                            </Button>
                                        </Link>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Creando...' : 'Crear flujo de trabajo'}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
