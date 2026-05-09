<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
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
                Rule::unique('equipments', 'code')->ignore($equipmentId),
            ],
            'description' => 'nullable|string',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',

            // Pianificazione manutenzione
            'maintenance_frequency_days' => 'required|integer|min:1',
            'assignment_type' => 'required|in:specializzazione,diretto',
            'maintenance_role_id' => 'nullable|exists:maintenance_roles,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'intervention_title' => 'required|string|max:255',
            'intervention_description' => 'nullable|string',
            'maintenance_situation' => 'required|in:mai,gia',
            'last_maintenance_date' => 'nullable|date',
            'first_intervention_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',

            'active' => 'nullable',

            // Componenti
            'components' => 'nullable|array',
            'components.*.name' => 'required_with:components.*|string|max:255',
            'components.*.frequency_days' => 'required_with:components.*|integer|min:1',
            'components.*.assignment_type' => 'required_with:components.*|in:specializzazione,diretto',
            'components.*.maintenance_role_id' => 'nullable|exists:maintenance_roles,id',
            'components.*.assigned_user_id' => 'nullable|exists:users,id',
            'components.*.intervention_title' => 'nullable|string|max:255',
            'components.*.intervention_description' => 'nullable|string',
            'components.*.maintenance_situation' => 'required_with:components.*|in:mai,gia',
            'components.*.last_maintenance_date' => 'nullable|date',
            'components.*.first_intervention_date' => 'nullable|date',
            'components.*.next_maintenance_date' => 'nullable|date',
            'components.*.description' => 'nullable|string',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->input('assignment_type') === 'specializzazione' && ! $this->filled('maintenance_role_id')) {
                $v->errors()->add('maintenance_role_id', 'Seleziona la specializzazione.');
            }
            if ($this->input('assignment_type') === 'diretto' && ! $this->filled('assigned_user_id')) {
                $v->errors()->add('assigned_user_id', 'Seleziona il manutentore.');
            }

            if ($this->input('maintenance_situation') === 'mai' && ! $this->filled('first_intervention_date')) {
                $v->errors()->add('first_intervention_date', 'Indica la data del primo intervento.');
            }
            if ($this->input('maintenance_situation') === 'gia' && ! $this->filled('last_maintenance_date')) {
                $v->errors()->add('last_maintenance_date', 'Indica la data dell\'ultima manutenzione eseguita.');
            }

            foreach ($this->input('components', []) as $i => $c) {
                $type = $c['assignment_type'] ?? null;
                if ($type === 'specializzazione' && empty($c['maintenance_role_id'])) {
                    $v->errors()->add("components.$i.maintenance_role_id", 'Seleziona la specializzazione del componente.');
                }
                if ($type === 'diretto' && empty($c['assigned_user_id'])) {
                    $v->errors()->add("components.$i.assigned_user_id", 'Seleziona il manutentore del componente.');
                }
                $sit = $c['maintenance_situation'] ?? null;
                if ($sit === 'mai' && empty($c['first_intervention_date'])) {
                    $v->errors()->add("components.$i.first_intervention_date", 'Indica la data del primo intervento del componente.');
                }
                if ($sit === 'gia' && empty($c['last_maintenance_date'])) {
                    $v->errors()->add("components.$i.last_maintenance_date", 'Indica l\'ultima manutenzione del componente.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Il reparto è obbligatorio',
            'department_id.exists' => 'Il reparto selezionato non esiste',
            'name.required' => 'Il nome dell\'impianto/macchina è obbligatorio',
            'code.unique' => 'Questo codice è già in uso',
            'maintenance_frequency_days.required' => 'La frequenza di manutenzione è obbligatoria',
            'maintenance_frequency_days.min' => 'La frequenza deve essere almeno 1 giorno',
            'assignment_type.required' => 'Indica come assegnare i ticket pianificati',
            'intervention_title.required' => 'Il titolo dell\'intervento pianificato è obbligatorio',
            'maintenance_situation.required' => 'Indica la situazione attuale dell\'impianto',
            'active.boolean' => 'Il valore del campo attivo non è valido',
        ];
    }
}
