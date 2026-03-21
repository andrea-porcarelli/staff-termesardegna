<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InterventionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && in_array(Auth::user()->role, ['admin', 'operator']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_type'                  => 'required|in:equipment,area',
            'equipment_id'                 => 'nullable|exists:equipments,id',
            'area_id'                      => 'nullable|exists:areas,id',
            'department_id'                => 'nullable|exists:departments,id',
            'assigned_user_id'             => 'required|exists:users,id',
            'title'                        => 'required|string|max:255',
            'description'                  => 'nullable|string',
            'scheduled_date'               => 'required|date',
            'scheduled_start_time'         => 'nullable|date_format:H:i',
            'estimated_duration_minutes'   => 'nullable|integer|min:1',
            'status'                       => 'nullable|in:planned,in_progress,completed,cancelled',
            'priority'                     => 'nullable|in:low,medium,high,critical',
            'notes'                        => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('target_type') === 'equipment' && !$this->filled('equipment_id')) {
                $validator->errors()->add('equipment_id', 'Seleziona un impianto/macchina.');
            }
            if ($this->input('target_type') === 'area') {
                if (!$this->filled('area_id')) {
                    $validator->errors()->add('area_id', 'Seleziona un\'area.');
                }
                if (!$this->filled('department_id')) {
                    $validator->errors()->add('department_id', 'Seleziona una zona.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'target_type.required'               => 'Seleziona se l\'intervento riguarda un impianto o un\'area/zona.',
            'target_type.in'                     => 'Tipo destinazione non valido.',
            'equipment_id.exists'                => 'L\'impianto selezionato non esiste.',
            'area_id.exists'                     => 'L\'area selezionata non esiste.',
            'department_id.exists'               => 'La zona selezionata non esiste.',
            'assigned_user_id.required'          => 'L\'operatore è obbligatorio.',
            'assigned_user_id.exists'            => 'L\'operatore selezionato non esiste.',
            'title.required'                     => 'Il titolo dell\'intervento è obbligatorio.',
            'title.max'                          => 'Il titolo non può superare 255 caratteri.',
            'scheduled_date.required'            => 'La data di pianificazione è obbligatoria.',
            'scheduled_date.date'                => 'La data non è valida.',
            'scheduled_start_time.date_format'   => 'L\'ora deve essere nel formato HH:MM.',
            'estimated_duration_minutes.integer' => 'La durata deve essere un numero intero.',
            'estimated_duration_minutes.min'     => 'La durata deve essere almeno 1 minuto.',
            'status.in'                          => 'Lo stato selezionato non è valido.',
            'priority.in'                        => 'La priorità selezionata non è valida.',
        ];
    }
}
