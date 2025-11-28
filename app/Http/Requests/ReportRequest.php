<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'report_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'activities' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,completed',
        ];
    }

    public function messages(): array
    {
        return [
            'report_date.required' => 'La data del rapportino è obbligatoria',
            'report_date.date' => 'La data non è valida',
            'start_time.date_format' => 'L\'ora di inizio deve essere nel formato HH:MM',
            'end_time.date_format' => 'L\'ora di fine deve essere nel formato HH:MM',
            'end_time.after' => 'L\'ora di fine deve essere successiva all\'ora di inizio',
            'status.in' => 'Lo stato selezionato non è valido',
        ];
    }
}
