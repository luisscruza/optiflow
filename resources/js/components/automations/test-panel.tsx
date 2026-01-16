import { AlertCircle, CheckCircle, Loader2, Play, XCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type WorkflowJob = {
    id: string;
    title: string;
    contact_name: string;
    stage_name: string;
    created_at: string;
};

type TestResult = {
    node_id: string;
    type: string;
    status: 'success' | 'error' | 'dry_run' | 'skipped';
    output: Record<string, unknown> | null;
};

type WorkflowOption = {
    id: string;
    name: string;
    stages: { id: string; name: string }[];
};

interface TestPanelProps {
    automationId?: string;
    workflows: WorkflowOption[];
    selectedWorkflowId?: string;
}

export function TestPanel({ automationId, workflows, selectedWorkflowId }: TestPanelProps) {
    const [workflowId, setWorkflowId] = useState(selectedWorkflowId || '');
    const [jobs, setJobs] = useState<WorkflowJob[]>([]);
    const [selectedJobId, setSelectedJobId] = useState('');
    const [loading, setLoading] = useState(false);
    const [loadingJobs, setLoadingJobs] = useState(false);
    const [results, setResults] = useState<TestResult[]>([]);
    const [dryRun, setDryRun] = useState(true);

    const loadJobs = async (wfId: string) => {
        if (!wfId) return;

        setLoadingJobs(true);
        try {
            const response = await fetch(`/api/automations/test-data?workflow_id=${wfId}`);
            const data = await response.json();
            setJobs(data.jobs || []);
            setSelectedJobId('');
        } catch (error) {
            console.error('Error loading jobs:', error);
        } finally {
            setLoadingJobs(false);
        }
    };

    const runTest = async () => {
        if (!automationId || !selectedJobId) return;

        setLoading(true);
        setResults([]);

        try {
            const response = await fetch(`/automations/${automationId}/test`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    job_id: selectedJobId,
                    dry_run: dryRun,
                }),
            });

            const data = await response.json();

            if (data.results) {
                setResults(data.results);
            }
        } catch (error) {
            console.error('Error running test:', error);
        } finally {
            setLoading(false);
        }
    };

    const getStatusIcon = (status: TestResult['status']) => {
        switch (status) {
            case 'success':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'error':
                return <XCircle className="h-4 w-4 text-red-500" />;
            case 'dry_run':
                return <AlertCircle className="h-4 w-4 text-amber-500" />;
            default:
                return <AlertCircle className="h-4 w-4 text-gray-400" />;
        }
    };

    const getStatusLabel = (status: TestResult['status']) => {
        switch (status) {
            case 'success':
                return 'Éxito';
            case 'error':
                return 'Error';
            case 'dry_run':
                return 'Simulado';
            default:
                return 'Omitido';
        }
    };

    if (!automationId) {
        return (
            <div className="rounded-lg border bg-card p-4">
                <p className="text-sm text-muted-foreground">Guarda la automatización primero para poder probarla.</p>
            </div>
        );
    }

    return (
        <div className="space-y-4 rounded-lg border bg-card p-4">
            <div>
                <h3 className="flex items-center gap-2 font-semibold">
                    <Play className="h-4 w-4" />
                    Modo de prueba
                </h3>
                <p className="text-xs text-muted-foreground">Prueba la automatización con datos reales</p>
            </div>

            <div className="space-y-3">
                <div className="space-y-2">
                    <Label>Flujo de trabajo</Label>
                    <Select
                        value={workflowId}
                        onValueChange={(v) => {
                            setWorkflowId(v);
                            loadJobs(v);
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Seleccionar flujo" />
                        </SelectTrigger>
                        <SelectContent>
                            {workflows.map((w) => (
                                <SelectItem key={w.id} value={w.id}>
                                    {w.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {loadingJobs ? (
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin" />
                        Cargando jobs...
                    </div>
                ) : jobs.length > 0 ? (
                    <div className="space-y-2">
                        <Label>Job de prueba</Label>
                        <Select value={selectedJobId} onValueChange={setSelectedJobId}>
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar job" />
                            </SelectTrigger>
                            <SelectContent>
                                {jobs.map((job) => (
                                    <SelectItem key={job.id} value={job.id}>
                                        <div className="flex flex-col">
                                            <span>{job.title}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {job.contact_name} • {job.stage_name}
                                            </span>
                                        </div>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                ) : workflowId ? (
                    <p className="text-sm text-muted-foreground">No hay jobs en este flujo</p>
                ) : null}

                <div className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="dry_run"
                        checked={dryRun}
                        onChange={(e) => setDryRun(e.target.checked)}
                        className="h-4 w-4 rounded border-gray-300"
                    />
                    <Label htmlFor="dry_run" className="cursor-pointer text-sm font-normal">
                        Solo simular (no enviar mensajes reales)
                    </Label>
                </div>

                <Button onClick={runTest} disabled={!selectedJobId || loading} className="w-full" variant={dryRun ? 'outline' : 'default'}>
                    {loading ? (
                        <>
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            Ejecutando...
                        </>
                    ) : (
                        <>
                            <Play className="mr-2 h-4 w-4" />
                            {dryRun ? 'Simular ejecución' : 'Ejecutar prueba'}
                        </>
                    )}
                </Button>
            </div>

            {results.length > 0 && (
                <div className="space-y-2 border-t pt-4">
                    <h4 className="text-sm font-medium">Resultados</h4>
                    <div className="space-y-2">
                        {results.map((result, idx) => (
                            <div
                                key={idx}
                                className={`rounded-md border p-2 ${
                                    result.status === 'success'
                                        ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950'
                                        : result.status === 'error'
                                          ? 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950'
                                          : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950'
                                }`}
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        {getStatusIcon(result.status)}
                                        <span className="text-xs font-medium">{result.type}</span>
                                    </div>
                                    <span className="text-xs text-muted-foreground">{getStatusLabel(result.status)}</span>
                                </div>
                                {result.output && (
                                    <pre className="mt-2 max-h-32 overflow-auto rounded bg-muted/50 p-2 text-xs">
                                        {JSON.stringify(result.output, null, 2)}
                                    </pre>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
