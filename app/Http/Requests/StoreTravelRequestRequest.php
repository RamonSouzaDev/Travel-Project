<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelRequestRequest extends FormRequest
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
        return [
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:departure_date',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'destination.required' => 'O destino é obrigatório.',
            'departure_date.required' => 'A data de ida é obrigatória.',
            'departure_date.after_or_equal' => 'A data de ida deve ser hoje ou uma data futura.',
            'return_date.required' => 'A data de volta é obrigatória.',
            'return_date.after_or_equal' => 'A data de volta deve ser igual ou posterior à data de ida.',
        ];
    }
}
