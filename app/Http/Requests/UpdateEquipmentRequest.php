<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
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
        $equipmentId = $this->route('equipment')->id;

        return [
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('equipments', 'code')->ignore($equipmentId)
            ],
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'maintenance_frequency_days' => 'required|integer|min:1',
            'last_maintenance_date' => 'nullable|date',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Il reparto è obbligatorio',
            'department_id.exists' => 'Il reparto selezionato non esiste',
            'name.required' => 'Il nome dell\'apparato è obbligatorio',
            'code.unique' => 'Questo codice è già in uso',
            'maintenance_frequency_days.required' => 'La frequenza di manutenzione è obbligatoria',
            'maintenance_frequency_days.min' => 'La frequenza deve essere almeno 1 giorno',
            'active.boolean' => 'Il valore del campo attivo non è valido',
        ];
    }
}
