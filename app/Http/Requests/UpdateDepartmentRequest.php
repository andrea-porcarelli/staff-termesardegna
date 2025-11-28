<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role == 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required' => 'L\'area è obbligatoria',
            'area_id.exists' => 'L\'area selezionata non esiste',
            'name.required' => 'Il nome del reparto è obbligatorio',
            'name.max' => 'Il nome non può superare i 255 caratteri',
            'active.boolean' => 'Il valore del campo attivo non è valido',
        ];
    }
}
