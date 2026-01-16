<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],

            'trigger_workflow_id' => ['required', 'uuid', 'exists:workflows,id'],
            'trigger_stage_id' => ['required', 'uuid', 'exists:workflow_stages,id'],

            // Visual builder format (nodes + edges)
            'nodes' => ['sometimes', 'array'],
            'nodes.*.id' => ['required_with:nodes', 'string'],
            'nodes.*.type' => ['required_with:nodes', 'string'],
            'nodes.*.position' => ['sometimes', 'array'],
            'nodes.*.position.x' => ['sometimes', 'numeric'],
            'nodes.*.position.y' => ['sometimes', 'numeric'],
            'nodes.*.config' => ['sometimes', 'array'],

            'edges' => ['sometimes', 'array'],
            'edges.*.from' => ['required_with:edges', 'string'],
            'edges.*.to' => ['required_with:edges', 'string'],
            'edges.*.sourceHandle' => ['nullable', 'string'],
            'edges.*.targetHandle' => ['nullable', 'string'],

            // Legacy form format (actions array) - kept for backward compatibility
            'actions' => ['sometimes', 'array'],
            'actions.*.type' => ['required_with:actions', 'string', 'in:http.webhook,telegram.send_message'],
            'actions.*.config' => ['required_with:actions', 'array'],

            // Webhook-specific config (only required if action type is webhook)
            'actions.*.config.url' => ['nullable', 'url'],
            'actions.*.config.method' => ['nullable', 'string', 'in:POST,PUT,PATCH,DELETE'],
            'actions.*.config.headers' => ['nullable', 'array'],
            'actions.*.config.body' => ['nullable'],

            // Telegram-specific config
            'actions.*.config.telegram_bot_id' => ['nullable', 'string'],
            'actions.*.config.bot_token' => ['nullable', 'string'],
            'actions.*.config.chat_id' => ['nullable', 'string'],
            'actions.*.config.message' => ['nullable', 'string'],
            'actions.*.config.parse_mode' => ['nullable', 'string', 'in:HTML,Markdown,MarkdownV2'],
            'actions.*.config.disable_notification' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'trigger_workflow_id.required' => 'El flujo de trabajo es obligatorio.',
            'trigger_stage_id.required' => 'La etapa es obligatoria.',
            'actions.required' => 'Debes agregar al menos una acción.',
            'actions.*.config.url.required' => 'El URL del webhook es obligatorio.',
        ];
    }
}
