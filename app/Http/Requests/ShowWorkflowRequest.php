<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ShowWorkflowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'filter_contact' => ['nullable', 'integer', 'exists:contacts,id'],
            'filter_priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'filter_status' => ['nullable', 'string', Rule::in(['pending', 'completed', 'canceled'])],
            'filter_due_status' => ['nullable', 'string', Rule::in(['overdue', 'not_overdue'])],
            'filter_date_from' => ['nullable', 'date'],
            'filter_date_to' => ['nullable', 'date', 'after_or_equal:filter_date_from'],
            'all_workspaces' => ['nullable', 'boolean'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
        ];
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'filter_contact.exists' => 'El contacto seleccionado no existe.',
            'filter_priority.in' => 'La prioridad debe ser: low, medium, high o urgent.',
            'filter_status.in' => 'El estado debe ser: pending, completed o canceled.',
            'filter_due_status.in' => 'El estado de vencimiento debe ser: overdue o not_overdue.',
            'filter_date_from.date' => 'La fecha inicial debe ser válida.',
            'filter_date_to.date' => 'La fecha final debe ser válida.',
            'filter_date_to.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'contact_id.exists' => 'El contacto seleccionado no existe.',
        ];
    }

    /**
     * Get validated filters as an array.
     *
     * @return array<string, mixed>
     */
    public function getFilters(): array
    {
        return [
            'contact_id' => $this->validated('filter_contact'),
            'priority' => $this->validated('filter_priority'),
            'status' => $this->validated('filter_status'),
            'due_status' => $this->validated('filter_due_status'),
            'date_from' => $this->validated('filter_date_from'),
            'date_to' => $this->validated('filter_date_to'),
        ];
    }

    /**
     * Check if all workspaces should be shown.
     */
    public function showAllWorkspaces(): bool
    {
        return (bool) $this->validated('all_workspaces', false);
    }

    /**
     * Get the contact ID for lazy loading.
     */
    public function getContactId(): ?string
    {
        return $this->validated('contact_id');
    }
}
