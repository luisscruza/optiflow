<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(#[CurrentUser] User $user): bool
    {
        return $user->hasBusinessRole(UserRole::Owner);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The workspace name is required.',
            'name.max' => 'The workspace name may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',
        ];
    }
}
