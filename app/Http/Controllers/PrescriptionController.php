<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreatePrescriptionAction;
use App\Models\Contact;
use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, CreatePrescriptionAction $action, #[CurrentUser] User $user): RedirectResponse
    {

        $action->handle($user, $request->all());

        return redirect()->back();
    }
}
