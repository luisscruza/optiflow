<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePrescriptionAction;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Inertia\Response;

final class PrescriptionController extends Controller
{
    public function create(): Response
    {
        $customers = Contact::query()->customers()->orderBy('name')
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

    public function index(Request $request): Response
    {
        $workspace = Context::get('workspace');

        $query = Prescription::query()
            ->with(['patient', 'optometrist', 'workspace'])
            ->where('workspace_id', $workspace->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;

            // Search by patient name or ID
            $query->whereHas('patient', function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identification_number', 'like', "%{$search}%");
            });
        }

        $prescriptions = $query->paginate(15)->withQueryString();

        return inertia('prescriptions/index', [
            'prescriptions' => $prescriptions,
        ]);
    }

    public function store(Request $request, CreatePrescriptionAction $action, #[CurrentUser] User $user): RedirectResponse
    {

        $action->handle($user, $request->all());

        return redirect()->back();
    }

    public function show(Prescription $prescription): Response
    {
        $prescription->load(['patient', 'optometrist', 'workspace']);

        return inertia('prescriptions/show', [
            'prescription' => $prescription,
        ]);
    }

    public function edit(Prescription $prescription): Response
    {
        $prescription->load(['patient', 'optometrist', 'workspace']);

        $customers = Contact::query()->customers()->orderBy('name')
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

        return inertia('prescriptions/edit', [
            'prescription' => $prescription,
            'customers' => $customers,
            'optometrists' => $optometrists,
            'masterTables' => $masterTables,
        ]);
    }

    public function update(Request $request, Prescription $prescription): RedirectResponse
    {
        // You'll need to create an UpdatePrescriptionAction similar to CreatePrescriptionAction
        // For now, let's add a basic update logic
        $prescription->update($request->all());

        return redirect()->route('prescriptions.index')->with('success', 'Receta actualizada exitosamente.');
    }

    public function destroy(Prescription $prescription): RedirectResponse
    {
        $prescription->delete();

        return redirect()->route('prescriptions.index')->with('success', 'Receta eliminada exitosamente.');
    }
}
