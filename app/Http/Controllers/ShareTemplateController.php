<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateShareTemplateAction;
use App\Actions\GetShareTemplateVariableGroupsAction;
use App\Actions\UpdateShareTemplateAction;
use App\Enums\Permission;
use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Http\Requests\CreateShareTemplateRequest;
use App\Http\Requests\UpdateShareTemplateRequest;
use App\Models\ShareTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShareTemplateController
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can(Permission::ConfigurationView), 403);

        return Inertia::render('configuration/share-templates-index', [
            'templates' => ShareTemplate::query()->orderBy('entity_type')->orderBy('channel')->get(),
            'entityOptions' => ShareTemplateEntity::options(),
            'channelOptions' => ShareTemplateChannel::options(),
        ]);
    }

    public function create(Request $request, GetShareTemplateVariableGroupsAction $action): Response
    {
        abort_unless($request->user()?->can(Permission::ConfigurationEdit), 403);

        return Inertia::render('configuration/share-templates-create', [
            'entityOptions' => ShareTemplateEntity::options(),
            'channelOptions' => ShareTemplateChannel::options(),
            'variableGroups' => $action->handle(),
        ]);
    }

    public function store(CreateShareTemplateRequest $request, CreateShareTemplateAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->route('share-templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(Request $request, ShareTemplate $shareTemplate, GetShareTemplateVariableGroupsAction $action): Response
    {
        abort_unless($request->user()?->can(Permission::ConfigurationEdit), 403);

        return Inertia::render('configuration/share-templates-edit', [
            'template' => $shareTemplate,
            'entityOptions' => ShareTemplateEntity::options(),
            'channelOptions' => ShareTemplateChannel::options(),
            'variableGroups' => $action->handle(),
        ]);
    }

    public function update(UpdateShareTemplateRequest $request, ShareTemplate $shareTemplate, UpdateShareTemplateAction $action): RedirectResponse
    {
        $action->handle($shareTemplate, $request->validated());

        return redirect()->route('share-templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }
}
