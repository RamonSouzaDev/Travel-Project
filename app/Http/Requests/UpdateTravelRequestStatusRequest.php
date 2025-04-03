<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelRequestStatusRequest extends FormRequest
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
        'status' => 'required|string|in:aprovado,cancelado',
            'reason_for_cancellation' => 'required_if:status,cancelado|nullable|string|max:500',
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
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser aprovado ou cancelado.',
            'reason_for_cancellation.required_if' => 'O motivo do cancelamento é obrigatório quando o status é cancelado.',
        ];
    }
}
