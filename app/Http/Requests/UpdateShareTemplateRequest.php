<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Enums\ShareTemplateChannel;
use App\Enums\ShareTemplateEntity;
use App\Models\ShareTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateShareTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::ConfigurationEdit) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ShareTemplate $shareTemplate */
        $shareTemplate = $this->route('share_template');

        return [
            'entity_type' => ['required', Rule::enum(ShareTemplateEntity::class)],
            'channel' => ['required', Rule::enum(ShareTemplateChannel::class)],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->string('channel')->value() === ShareTemplateChannel::Email->value)],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'template_pair' => [
                Rule::unique('share_templates', 'entity_type')->ignore($shareTemplate->id)->where(
                    fn ($query) => $query->where('channel', $this->string('channel')->value())
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'entity_type.required' => 'Debes seleccionar la entidad.',
            'entity_type.enum' => 'La entidad seleccionada no es valida.',
            'channel.required' => 'Debes seleccionar el canal.',
            'channel.enum' => 'El canal seleccionado no es valido.',
            'name.required' => 'El nombre de la plantilla es obligatorio.',
            'subject.required' => 'El asunto es obligatorio para las plantillas de correo.',
            'body.required' => 'El contenido de la plantilla es obligatorio.',
            'template_pair.unique' => 'Ya existe una plantilla para esa entidad y canal.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        unset($validated['template_pair']);

        return $validated;
    }
}
