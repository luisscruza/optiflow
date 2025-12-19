<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InviteBusinessUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'workspaces' => ['required', 'array', 'min:1'],
            'workspaces.*.workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'workspaces.*.role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ];
    }
}
