<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContactType;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class UpdateExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $expenseId = (int) $this->route('expense');

        return [
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where(fn ($query) => $query
                    ->where('contact_type', ContactType::Supplier->value)
                    ->where('status', 'active')),
            ],
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Expense::class, 'document_number')
                    ->ignore($expenseId)
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $this->integer('workspace_id'))
                        ->where('contact_id', $this->integer('contact_id'))),
            ],
            'issue_date' => ['required', 'date'],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'itbis_amount' => ['required', 'numeric', 'min:0'],
            'isc_amount' => ['required', 'numeric', 'min:0'],
            'withheld_itbis_amount' => ['required', 'numeric', 'min:0'],
            'withheld_isr_amount' => ['required', 'numeric', 'min:0'],
            'is_informal' => ['required', 'boolean'],
            'status' => ['required', Rule::enum(ExpenseStatus::class)],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', File::types(['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'])->max(10 * 1024)],
            'remove_attachment_ids' => ['nullable', 'array'],
            'remove_attachment_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.exists' => 'Debes seleccionar un suplidor válido.',
            'document_number.unique' => 'Ya existe un gasto con ese número de comprobante para este suplidor en la sucursal seleccionada.',
            'attachments.max' => 'No puedes subir más de 10 archivos.',
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido.',
            'attachments.*.max' => 'Cada adjunto no puede exceder 10MB.',
            'attachments.*.types' => 'Los adjuntos deben ser PDF o imágenes.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('workspace_id') && $this->user()?->current_workspace_id) {
            $this->merge([
                'workspace_id' => $this->user()?->current_workspace_id,
            ]);
        }
    }
}
