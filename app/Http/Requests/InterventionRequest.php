<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && in_array(Auth::user()->role, ['admin', 'operator']);
    }

    public function rules(): array
    {
        $tipo = $this->input('tipo');

        return [
            'tipo'                         => 'required|in:pianificazione,ordinario',
            'equipment_id'                 => 'nullable|exists:equipments,id',
            'area_id'                      => 'nullable|exists:areas,id',
            'department_id'                => 'nullable|exists:departments,id',
            'assigned_user_id'             => 'required|exists:users,id',
            'title'                        => 'required|string|max:255',
            'description'                  => 'nullable|string',
            'scheduled_date'               => $tipo === 'pianificazione' ? 'required|date' : 'nullable|date',
            'scheduled_start_time'         => $tipo === 'pianificazione' ? 'nullable|date_format:H:i' : 'prohibited',
            'estimated_duration_minutes'   => $tipo === 'pianificazione' ? 'nullable|integer|min:1' : 'prohibited',
            'status'                       => 'nullable|in:planned,in_progress,completed,cancelled',
            'priority'                     => 'nullable|in:low,medium,high,critical',
            'notes'                        => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('tipo') === 'pianificazione' && !$this->filled('equipment_id')) {
                $validator->errors()->add('equipment_id', 'Seleziona un impianto/macchina.');
            }
            if ($this->input('tipo') === 'ordinario') {
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
            'tipo.required'                      => 'Seleziona il tipo di intervento.',
            'tipo.in'                            => 'Tipo intervento non valido.',
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
            'scheduled_start_time.prohibited'    => 'L\'ora di inizio non è prevista per gli interventi ordinari.',
            'estimated_duration_minutes.integer' => 'La durata deve essere un numero intero.',
            'estimated_duration_minutes.min'     => 'La durata deve essere almeno 1 minuto.',
            'estimated_duration_minutes.prohibited' => 'La durata non è prevista per gli interventi ordinari.',
            'status.in'                          => 'Lo stato selezionato non è valido.',
            'priority.in'                        => 'La priorità selezionata non è valida.',
        ];
    }
}
