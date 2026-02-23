<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')->id;

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($userId)
            ],
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,operator,manutentore',
            'maintenance_role_id' => 'nullable|exists:maintenance_roles,id',
            'teams' => 'nullable|array',
            'teams.*' => 'exists:teams,id',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Il nome è obbligatorio',
            'email.required' => 'L\'email è obbligatoria',
            'email.email' => 'L\'email non è valida',
            'email.unique' => 'Questa email è già in uso',
            'password.min' => 'La password deve essere di almeno 6 caratteri',
            'password.confirmed' => 'Le password non corrispondono',
            'role.required' => 'Il ruolo è obbligatorio',
            'role.in' => 'Il ruolo selezionato non è valido',
            'departments.array' => 'I reparti devono essere un array',
            'departments.*.exists' => 'Uno o più reparti selezionati non esistono',
        ];
    }
}
