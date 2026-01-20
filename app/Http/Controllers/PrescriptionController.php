<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePrescriptionAction;
use App\Actions\UpdatePrescriptionAction;
use App\Enums\Permission;
use App\Http\Requests\CreatePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\Prescription;
use App\Models\User;
use App\Tables\PrescriptionsTable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Response;

final class PrescriptionController extends Controller
{
    public function create(#[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PrescriptionsCreate), 403);

        $customers = Contact::query()->orderBy('name')
            ->get();

        $optometrists = Contact::query()->optometrists()->orderBy('name')
            ->get();

        $masterTables = Mastertable::with('items')
            ->whereIn('alias', [
                'motivos_consulta',
                'estado_salud_actual',
                'historia_ocular_familiar',
                'tipos_de_lentes',
                'tipos_de_montura',
                'tipos_de_gotas',
            ])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Mastertable $mastertable): array => [
                $mastertable->alias => [
                    'id' => $mastertable->id,
                    'name' => $mastertable->name,
                    'alias' => $mastertable->alias,
                    'description' => $mastertable->description,
                    'items' => $mastertable->items
                        ->sortBy('name')
                        ->map(fn (MastertableItem $item): array => [
                            'id' => $item->id,
                            'mastertable_id' => $item->mastertable_id,
                            'name' => $item->name,
                        ])->values()->all(),
                ],
            ])->all();

        return inertia('prescriptions/create', [
            'customers' => $customers,
            'optometrists' => $optometrists,
            'masterTables' => $masterTables,
        ]);
    }

    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PrescriptionsView), 403);

        return inertia('prescriptions/index', [
            'prescriptions' => PrescriptionsTable::make($request),
        ]);
    }

    public function store(CreatePrescriptionRequest $request, CreatePrescriptionAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::PrescriptionsCreate), 403);

        $action->handle($user, $request->validated());

        return redirect()->back();
    }

    public function show(Prescription $prescription, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PrescriptionsView), 403);

        $workspace = Context::get('workspace');
        abort_unless($prescription->workspace_id === $workspace->id, 404);

        $prescription->load([
            'patient',
            'optometrist',
            'workspace',
            'motivos',
            'estadoActual',
            'historiaOcularFamiliar',
        ]);

        return inertia('prescriptions/show', [
            'prescription' => $prescription,
        ]);
    }

    public function edit(Prescription $prescription, #[CurrentUser] User $user): Response
    {
        abort_unless($user->can(Permission::PrescriptionsEdit), 403);

        $prescription->load([
            'patient',
            'optometrist',
            'workspace',
            'motivos',
            'estadoActual',
            'historiaOcularFamiliar',
        ]);

        $customers = Contact::query()->orderBy('name')
            ->get();

        $optometrists = Contact::query()->optometrists()->orderBy('name')
            ->get();

        $masterTables = Mastertable::with('items')
            ->whereIn('alias', [
                'motivos_consulta',
                'estado_salud_actual',
                'historia_ocular_familiar',
                'tipos_de_lentes',
                'tipos_de_montura',
                'tipos_de_gotas',
            ])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Mastertable $mastertable): array => [
                $mastertable->alias => [
                    'id' => $mastertable->id,
                    'name' => $mastertable->name,
                    'alias' => $mastertable->alias,
                    'description' => $mastertable->description,
                    'items' => $mastertable->items
                        ->sortBy('name')
                        ->map(fn (MastertableItem $item): array => [
                            'id' => $item->id,
                            'mastertable_id' => $item->mastertable_id,
                            'name' => $item->name,
                        ])->values()->all(),
                ],
            ])->all();

        // Transform prescription for the form
        $prescriptionData = $prescription->toArray();
        $prescriptionData['motivos_consulta'] = $prescription->motivos->pluck('id')->all();
        $prescriptionData['estado_salud_actual'] = $prescription->estadoActual->pluck('id')->all();
        $prescriptionData['historia_ocular_familiar'] = $prescription->historiaOcularFamiliar->pluck('id')->all();

        return inertia('prescriptions/edit', [
            'prescription' => $prescriptionData,
            'customers' => $customers,
            'optometrists' => $optometrists,
            'masterTables' => $masterTables,
        ]);
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription, UpdatePrescriptionAction $action, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::PrescriptionsEdit), 403);

        $action->handle($prescription, $user, $request->validated());

        return redirect()->route('prescriptions.show', $prescription);
    }
}
