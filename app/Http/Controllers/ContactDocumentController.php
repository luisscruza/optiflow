<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\StoreContactDocumentRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ContactDocumentController
{
    public function store(StoreContactDocumentRequest $request, Contact $contact, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        /** @var array<\Illuminate\Http\UploadedFile> $documents */
        $documents = $request->validated()['documents'];

        foreach ($documents as $document) {
            $contact->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Documentos cargados correctamente.');
    }

    public function destroy(Contact $contact, Media $media, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->can(Permission::ContactsEdit), 403);

        abort_unless(
            $media->model_type === Contact::class
            && (int) $media->model_id === $contact->id
            && $media->collection_name === 'documents',
            404,
        );

        $media->delete();

        return redirect()->route('contacts.show', $contact)
            ->with('success', 'Documento eliminado correctamente.');
    }
}
