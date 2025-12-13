<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateAreaRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Il nome dell\'area è obbligatorio',
            'name.max' => 'Il nome non può superare i 255 caratteri',
            'active.boolean' => 'Il valore del campo attivo non è valido',
        ];
    }
}
