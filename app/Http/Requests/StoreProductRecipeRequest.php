<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Mastertable;
use App\Models\MastertableItem;
use App\Models\ProductRecipe;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRecipeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productsMastertableId = Mastertable::query()
            ->where('alias', ProductRecipe::PRODUCTS_MASTERTABLE_ALIAS)
            ->value('id');

        return [
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'optometrist_id' => ['required', 'integer', 'exists:contacts,id'],
            'product_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($productsMastertableId): void {
                    if ($productsMastertableId === null) {
                        $fail('La tabla maestra de productos no esta disponible.');

                        return;
                    }

                    $exists = MastertableItem::query()
                        ->whereKey($value)
                        ->where('mastertable_id', $productsMastertableId)
                        ->exists();

                    if (! $exists) {
                        $fail('El producto seleccionado no es valido.');
                    }
                },
            ],
            'indication' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'El contacto es obligatorio.',
            'contact_id.exists' => 'El contacto seleccionado no es válido.',
            'optometrist_id.required' => 'El evaluador es obligatorio.',
            'optometrist_id.exists' => 'El evaluador seleccionado no es válido.',
            'product_id.required' => 'El producto es obligatorio.',
            'product_id.exists' => 'El producto seleccionado no es válido.',
            'indication.max' => 'La indicación no puede exceder 5000 caracteres.',
        ];
    }
}
