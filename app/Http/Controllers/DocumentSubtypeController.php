<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDocumentSubtypeAction;
use App\Actions\UpdateDocumentSubtypeAction;
use App\Http\Requests\CreateDocumentSubtypeRequest;
use App\Http\Requests\UpdateDocumentSubtypeRequest;
use App\Models\DocumentSubtype;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentSubtypeController extends Controller
{
    /**
     * Display a listing of document subtypes (numeraciones).
     */
    public function index(Request $request): Response
    {
        $documentType = $request->get('document_type', 'Factura de venta');

        $query = DocumentSubtype::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%");
            });
        }

        $subtypes = $query->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $subtypes->getCollection()->transform(function ($subtype) {
            $subtype->setAttribute('siguiente_numero', $subtype->next_number);
            $subtype->setAttribute('fecha_finalizacion', $subtype->valid_until_date?->format('d/m/Y'));
            $subtype->setAttribute('preferida', $subtype->is_default ? 'Sí' : 'No');

            return $subtype;
        });

        return Inertia::render('document-subtypes/index', [
            'subtypes' => $subtypes,
            'filters' => [
                'search' => $request->get('search'),
                'document_type' => $documentType,
            ],
        ]);
    }

    /**
     * Show the form for creating a new document subtype.
     */
    public function create(): Response
    {
        return Inertia::render('document-subtypes/create');
    }

    /**
     * Store a newly created document subtype.
     */
    public function store(CreateDocumentSubtypeRequest $request, CreateDocumentSubtypeAction $action, User $user): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return redirect()->route('document-subtypes.index')
            ->with('success', 'Numeración creada exitosamente.');
    }

    /**
     * Display the specified document subtype.
     */
    public function show(DocumentSubtype $documentSubtype): Response
    {
        $documentSubtype->load('preferredByWorkspaces');

        $availableWorkspaces = Auth::user()?->workspaces ?? collect();

        $workspacePreferences = $documentSubtype->preferredByWorkspaces
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => true])
            ->toArray();

        return Inertia::render('document-subtypes/show', [
            'subtype' => $documentSubtype,
            'availableWorkspaces' => $availableWorkspaces,
            'workspacePreferences' => $workspacePreferences,
        ]);
    }

    /**
     * Show the form for editing the specified document subtype.
     */
    public function edit(DocumentSubtype $documentSubtype): Response
    {
        return Inertia::render('document-subtypes/edit', [
            'subtype' => $documentSubtype,
        ]);
    }

    /**
     * Update the specified document subtype (limited fields only).
     */
    public function update(
        UpdateDocumentSubtypeRequest $request,
        UpdateDocumentSubtypeAction $action,
        DocumentSubtype $documentSubtype,
        User $user
    ): RedirectResponse {
        $action->handle($user, $documentSubtype, $request->validated());

        return redirect()->route('document-subtypes.show', $documentSubtype)
            ->with('success', 'Numeración actualizada exitosamente.');
    }
}
