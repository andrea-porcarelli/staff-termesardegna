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
        return [
            'tipo' => 'required|in:pianificazione,ordinario',
            'equipment_id' => 'nullable|exists:equipments,id',
            'component_id' => 'nullable|exists:equipment_components,id',
            'maintenance_role_id' => 'nullable|exists:maintenance_roles,id',
            'area_id' => 'nullable|exists:areas,id',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'assignment_type' => 'nullable|in:specializzazione,diretto',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'scheduled_start_time' => 'nullable|date_format:H:i',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'status' => 'nullable|in:open,planned,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,high,fixed_date',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipo = $this->input('tipo');

            if ($tipo === 'pianificazione') {
                if (! $this->filled('equipment_id')) {
                    $validator->errors()->add('equipment_id', 'Seleziona un impianto/macchina.');
                }
                $assignType = $this->input('assignment_type', 'specializzazione');
                if ($assignType === 'specializzazione' && ! $this->filled('maintenance_role_id')) {
                    $validator->errors()->add('maintenance_role_id', 'Seleziona una specializzazione.');
                } elseif ($assignType === 'diretto' && ! $this->filled('assigned_user_id')) {
                    $validator->errors()->add('assigned_user_id', 'Seleziona un operatore.');
                }
                if (! $this->filled('scheduled_date')) {
                    $validator->errors()->add('scheduled_date', 'La data di pianificazione è obbligatoria.');
                }
            }

            if ($tipo === 'ordinario') {
                if (! $this->filled('area_id')) {
                    $validator->errors()->add('area_id', "Seleziona un'area.");
                }
                if (! $this->filled('department_id')) {
                    $validator->errors()->add('department_id', 'Seleziona una zona.');
                }
                if (! $this->filled('maintenance_role_id')) {
                    $validator->errors()->add('maintenance_role_id', 'Seleziona una specializzazione.');
                }
                if ($this->input('priority') === 'fixed_date' && ! $this->filled('scheduled_date')) {
                    $validator->errors()->add('scheduled_date', 'Seleziona una data per il ticket a data fissa.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Seleziona il tipo di ticket.',
            'tipo.in' => 'Tipo ticket non valido.',
            'equipment_id.exists' => "L'impianto selezionato non esiste.",
            'component_id.exists' => 'Il componente selezionato non esiste.',
            'maintenance_role_id.exists' => 'La specializzazione selezionata non esiste.',
            'area_id.exists' => "L'area selezionata non esiste.",
            'department_id.exists' => 'La zona selezionata non esiste.',
            'assigned_user_id.exists' => "L'operatore selezionato non esiste.",
            'title.required' => 'Il titolo del ticket è obbligatorio.',
            'title.max' => 'Il titolo non può superare 255 caratteri.',
            'scheduled_date.date' => 'La data non è valida.',
            'scheduled_start_time.date_format' => "L'ora deve essere nel formato HH:MM.",
            'estimated_duration_minutes.integer' => 'La durata deve essere un numero intero.',
            'estimated_duration_minutes.min' => 'La durata deve essere almeno 1 minuto.',
            'status.in' => 'Lo stato selezionato non è valido.',
            'priority.in' => 'La priorità selezionata non è valida.',
        ];
    }
}
